<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class iParam
{
    private $config;
    private $auth_url = "https://api.ipara.com/rest/payment/auth";
    private $three_d_url = "https://api.ipara.com/rest/payment/threed";
    private $bin_lookup_url = "https://api.ipara.com/rest/payment/bin/lookup/v2";
    private $refund_url_inquiry = "https://api.ipara.com/corporate/payment/refund/inquiry";
    private $refund_url = "https://api.ipara.com/corporate/payment/refund";
    private $version = "1.0";
    private $public_key;
    private $private_key;
    private $mode;
    private $installment;
    private $amount;
    private $amount_fee;
    private $order_id;
    private $vendor_id;
    private $echo;
    private $success_url;
    private $failure_url;
    private $card;
    private $purchaser;
    private $products;
    private $shipping_address;
    private $invoice_address;
    private $three_d;

    public function __construct()
    {
        $this->config = $this->config();
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property))
            $this->$property = $value;

        return $this;
    }

    /**
     * Şu anın tarih ve zaman bilgisi
     *
     * @return false|string
     */
    protected function now()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Kullanıcı Gerçek IP Adresi
     *
     * @return string
     */
    public function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    /**
     * 3D Secure HTML Form
     *
     * @param $parameters
     * @param $endpoint
     * @return string
     */
    protected function toHtml($parameters, $endpoint)
    {
        $html = '<html lang="tr-TR">';
        $html .= '<head>';
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<title>3D Secure</title>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<form action="' . $endpoint . '" method="post" id="three_d_form">';
        $html .= '<input type="hidden" name="parameters" value="' . htmlspecialchars($parameters) . '" />';
        $html .= '<input type="submit" value="Öde" style="display:none;" />';
        $html .= '<noscript>';
        $html .= '<br />';
        $html .= '<br />';
        $html .= '<p class="text-align: center">';
        $html .= '<h1>3D Secure Yönlendirme İşlemi</h1>';
        $html .= '<h2>Javascript internet tarayıcınızda kapatılmış veya desteklenmiyor.<br /></h2>';
        $html .= '<h3>Lütfen banka 3D Secure sayfasına yönlenmek için tıklayınız.</h3>';
        $html .= '<input type="submit" value="3D Secure Sayfasına Yönlen" />';
        $html .= '</center>';
        $html .= '</noscript>';
        $html .= '</form>';
        $html .= '<script>document.getElementById("three_d_form").submit();</script>';
        $html .= '</body>';
        $html .= '</html>';

        return $html;
    }

    /**
     * 3D XML İstek
     *
     * @param $parameters
     * @return string
     */
    protected function toXml($parameters)
    {
        if ($parameters['threeD'])
            $threeD = "true";
        else
            $threeD = "false";

        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<auth>\n";
        $xml .= "<threeD>{$threeD}</threeD>\n";
        $xml .= "<orderId>{$parameters['orderId']}</orderId>\n";
        $xml .= "<amount>{$parameters['amount']}</amount>\n";
        $xml .= "<cardOwnerName>". urlencode($parameters['cardOwnerName']) . "</cardOwnerName>\n";
        $xml .= "<cardNumber>{$parameters['cardNumber']}</cardNumber>\n";
        $xml .= "<cardExpireMonth>{$parameters['cardExpireMonth']}</cardExpireMonth>\n";
        $xml .= "<cardExpireYear>{$parameters['cardExpireYear']}</cardExpireYear>\n";
        $xml .= "<installment>{$parameters['installment']}</installment>\n";
        $xml .= "<cardCvc>{$parameters['cardCvc']}</cardCvc>\n";
        $xml .= "<userId>{$parameters['userId']}</userId>\n";
        $xml .= "<cardId>{$parameters['cardId']}</cardId>\n";
        $xml .= "<mode>{$parameters['mode']}</mode>\n";

        if (!is_null($parameters['vendorId']))
            $xml .= "<vendorId>{$parameters['vendorId']}</vendorId>\n";

        if ($parameters['threeD'])
            $xml .= "<threeDSecureCode>{$parameters['threeDSecureCode']}</threeDSecureCode>\n";

        $xml .= "<products>";
        foreach ($parameters['products'] as $product) {
            $xml .= "<product>";
                $xml .= "<productCode>". urlencode($product['productCode']) . "</productCode>";
                $xml .= "<productName>". urlencode($product['productName']) . "</productName>";
                $xml .= "<quantity>{$product['quantity']}</quantity>";
                $xml .= "<price>{$product['price']}</price>";
            $xml .= "</product>";
        }
        $xml .= "</products>";
        $xml .= "<purchaser>\n";
            $xml .= "<name>". urlencode($parameters['purchaser']['name']) . "</name>\n";
            $xml .= "<surname>". urlencode($parameters['purchaser']['surname']). "</surname>\n";
            $xml .= "<email>{$parameters['purchaser']['email']}</email>\n";
            $xml .= "<clientIp>{$parameters['purchaser']['clientIp']}</clientIp>\n";
        $xml .= "</purchaser>\n";
        $xml .= "</auth>";

        return $xml;
    }

    /**
     * Modül konfigürasyonu
     *
     * @return array
     */
    public function config()
    {
        try {
            $params = Capsule::table('tblpaymentgateways')
                ->where('gateway', 'iparam')->get();

            $config = [];

            foreach ($params as $param) {
                $config[$param->setting] = $param->value;
            }

            return $config;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * Ödeme Çekim İşlemi
     *
     * @param $params
     * @param $post
     * @return string
     */
    public function capture($params, $post)
    {
        // ödenecek tutar
        $amount = (float) $params['amount'];

        // taksit
        $this->installment = $post['iparam_installment'];
        $installmentKey = "iparam_{$this->installment}_installment";
        $installmentRate = (100 + $this->config[$installmentKey]) / 100;

        // gerçek ödenecek tutar
        $this->amount = number_format($amount * $installmentRate, 2, '', '');

        // komisyon
        $this->amount_fee = number_format(($this->amount - $amount), 2);

        // açık anahtar
        $this->public_key = $this->config['iparam_publickey'];

        // gizli anahtar
        $this->private_key = $this->config['iparam_privatekey'];

        // mağaza modu
        $this->mode = $this->config['iparam_testmode'];

        // sipariş numarası
        $this->order_id = sprintf("%sIPARAM%s", uniqid(), $params['invoiceid']);

        $this->vendor_id = '';

        // Echo mesajı
        $this->echo = 'Echo Message';

        // Başarılı ödeme dönüş adresi
        $this->success_url = $params['systemurl'] . 'modules/gateways/callback/iparam/payment-success.php';

        // Başarısız ödeme dönüş adresi
        $this->failure_url = $params['systemurl'] . 'modules/gateways/callback/iparam/payment-error.php';

        // Kart Bilgileri
        $this->card = [
            'owner_name' => $post['ccowner'] ?? $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'],
            'number' => $params['cardnum'],
            'expire_month' => substr($params['cardexp'], 0, 2),
            'expire_year' => substr($params['cardexp'], 2, 4),
            'cvc' => $params['cccvv'],
        ];

        // Ödeme Yapan
        $this->purchaser = [
            'name' => $params['clientdetails']['firstname'],
            'surname' => $params['clientdetails']['lastname'],
            'email' => $params['clientdetails']['email'],
            'clientIp' => $this->getClientIp(),
        ];

        // Ürünler
        $this->products = [
            0 => [
                'productCode' => $params['invoiceid'],
                'productName' => $params['description'],
                'quantity' => 1,
                'price' => $params['amount'],
            ],
        ];

        // Adres
        $address = [];
        $address['name'] = $this->purchaser['name'];
        $address['surname'] = $this->purchaser['surname'];
        $address['address'] = $params['clientdetails']['address1'] . ' ' . $params['clientdetails']['address2'] . ' - ' . $params['clientdetails']['state'];
        $address['zipcode'] = $params['clientdetails']['postcode'];
        $address['city_code'] = 34;
        $address['city'] = $params['clientdetails']['city'];
        $address['country_code'] = $params['clientdetails']['countrycode'];
        $address['country_text'] = $params['clientdetails']['country'];
        $address['phone_number'] = $params['clientdetails']['phonenumber'];
        $address['tax_number'] = null;
        $address['tax_office'] = null;
        $address['tc_certificate_number'] = null;
        $address['company_name'] = $params['clientdetails']['companyname'];

        $this->shipping_address = $address;
        $this->invoice_address = $address;

        // BIN Sorgulama
        $check_iPara = $this->binLookup($this->card['number'], $amount);

        // Bazı kartlar bin sorgulamasından geçemediği için taksit 1 olarak zorunlu yapıyoruz burada.
        if (!$check_iPara or $check_iPara['result'] == 0) {
            $check_iPara = [
                'result' => 1,
                'supportsInstallment' => 1,
                'cardThreeDSecureMandatory' => 1,
                'merchantThreeDSecureMandatory' => 1,
            ];

            $this->installment = 1;
        }

        // Taksitli Ödeme Kontrolü
        if ($check_iPara['supportsInstallment'] != 1 and $this->installment != 1) {
            $_SESSION['IPARAM_PAY_TYPE'] = ($this->config['iparam_3dmode'] == "on") ? "3D" : "API";
            $_SESSION['IPARAM_ERROR_CODE'] = "IPARA-ERROR-INSTALLMENT";
            $_SESSION['IPARAM_ERROR_MESSAGE'] = "Kartınız taksitli alışverişi desteklemiyor. Lütfen tek çekim olarak deneyiniz.";
            $_SESSION['IPARAM_RESULT'] = false;
            $_SESSION['IPARAM_ORDER_ID'] = "XXXIPARAM" . $params['invoiceid'];
            $_SESSION['IPARAM_HASH'] = "XXX" . uniqid();

            header("Location: {$this->failure_url}");
            exit;
        }

        $this->three_d = true;

        if ($this->config['iparam_3dmode'] == "on")
            $this->three_d = true;
        elseif ($this->config['iparam_3dmode'] == "off")
            $this->three_d = false;

        if ($check_iPara['cardThreeDSecureMandatory'] == 0
            and $check_iPara['merchantThreeDSecureMandatory'] == 0)
            $this->three_d = false;

        if ($this->three_d) {
            try {
                echo $this->payThreeD();
                exit;
            } catch (Exception $e) {
                $_SESSION['IPARAM_PAY_TYPE'] = "3D";
                $_SESSION['IPARAM_ERROR_CODE'] = "IPARA-ERROR-3DPAY";
                $_SESSION['IPARAM_ERROR_MESSAGE'] = $e->getMessage();
                $_SESSION['IPARAM_RESULT'] = false;
                $_SESSION['IPARAM_ORDER_ID'] = "XXXIPARAM" . $params['invoiceid'];
                $_SESSION['IPARAM_HASH'] = "XXX" . uniqid();

                header("Location: {$this->failure_url}");
                exit;
            }
        } else {
            try {
                $normal = $this->payNormal();
                $xmlDecoded = simplexml_load_string($normal, 'SimpleXMLElement', LIBXML_NOCDATA);
                $xmlDecodedArray = json_decode(json_encode($xmlDecoded), true);

                $_SESSION['IPARAM_PAY_TYPE'] = "API";
                $_SESSION['IPARAM_ERROR_CODE'] = $xmlDecodedArray['errorCode'] ?? null;
                $_SESSION['IPARAM_ERROR_MESSAGE'] = $xmlDecodedArray['errorMessage'] ?? null;
                $_SESSION['IPARAM_RESULT'] = $xmlDecodedArray['result'] == "1";
                $_SESSION['IPARAM_ORDER_ID'] = $xmlDecodedArray['orderId'];
                $_SESSION['IPARAM_HASH'] = $xmlDecodedArray['hash'];

                if ($_SESSION['IPARAM_RESULT']) {
                    header("Location: {$this->success_url}");
                    exit;
                } else {
                    header("Location: {$this->failure_url}");
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['IPARAM_PAY_TYPE'] = "API";
                $_SESSION['IPARAM_ERROR_CODE'] = "IPARA-ERROR-PAY";
                $_SESSION['IPARAM_ERROR_MESSAGE'] = $e->getMessage();
                $_SESSION['IPARAM_RESULT'] = false;
                $_SESSION['IPARAM_ORDER_ID'] = "XXXIPARAM" . $params['invoiceid'];
                $_SESSION['IPARAM_HASH'] = "XXX" . uniqid();

                header("Location: {$this->failure_url}");
                exit;
            }
        }
    }

    /**
     * BIN sorgulama
     *
     * @param $cc
     * @param $amount
     * @return array|false
     */
    public function binLookup($cc, $amount)
    {
        $binNumber = substr($cc, 0, 6);
        $transactionDate = $this->now();
        $token = $this->public_key . ':' . base64_encode(sha1($this->private_key . $binNumber . $transactionDate, true));

        $data = [
            'binNumber' => $binNumber,
            'amount' => $amount,
            'threeD' => true,
        ];

        $dataString = json_encode($data);

        $ch = curl_init($this->bin_lookup_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length:' . strlen($dataString),
            'token:' . $token,
            'transactionDate:' . $transactionDate,
            'version:' . '1.0',
        ));

        $response = curl_exec($ch);

        if (!is_null($response) and $response != false)
            return json_decode($response, true);
        else
            return false;
    }

    /**
     * 3D Secure Olmadan Ödeme İsteği
     *
     * @return string
     */
    public function payNormal($userId = "", $cardId = "")
    {
        $now = $this->now();

        $hash_text = $this->private_key .
            $this->order_id .
            $this->amount .
            $this->mode .
            $this->card['owner_name'] .
            $this->card['number'] .
            $this->card['expire_month'] .
            $this->card['expire_year'] .
            $this->card['cvc'] .
            $this->purchaser['name'] .
            $this->purchaser['surname'] .
            $this->purchaser['email'] .
            $now;

        $token = $this->public_key . ":" . base64_encode(sha1($hash_text, true));

        $params = [
            'threeD' => false,
            'threeDSecureCode' => null,
            'orderId' => $this->order_id,
            'amount' => $this->amount,
            'cardOwnerName' => ($userId != "" && $cardId != "") ? "" : $this->card['owner_name'],
            'cardNumber' => ($userId != "" && $cardId != "") ? "" : $this->card['number'],
            'cardExpireMonth' => ($userId != "" && $cardId != "") ? "" : $this->card['expire_month'],
            'cardExpireYear' => ($userId != "" && $cardId != "") ? "" : $this->card['expire_year'],
            'userId' => $userId,
            'cardId' => $cardId,
            'installment' => $this->installment,
            'cardCvc' => $this->card['cvc'],
            'mode' => $this->mode,
            'purchaser' => [
                'name' => $this->purchaser['name'],
                'surname' => $this->purchaser['surname'],
                'email' => $this->purchaser['email'],
                'clientIp' => $this->purchaser['clientIp'],
            ],
            'products' => $this->products,
            'successUrl' => $this->success_url,
            'failureUrl' => $this->failure_url,
            'echo' => $this->echo,
            'version' => $this->version,
            'language' => 'tr-TR',
            'vendorId' => $this->vendor_id,
        ];

        $xmlParams = $this->toXml($params);

        $ch = curl_init($this->auth_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/xml',
            'Content-Length:' . strlen($xmlParams),
            'token:' . $token,
            'transactionDate:' . $now,
            'version:' . '1.0',
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * 3D Secure Ödeme İsteği
     *
     * @return string
     */
    public function payThreeD()
    {
        $now = $this->now();

        $hash_text = $this->private_key .
            $this->order_id .
            $this->amount .
            $this->mode .
            $this->card['owner_name'] .
            $this->card['number'] .
            $this->card['expire_month'] .
            $this->card['expire_year'] .
            $this->card['cvc'] .
            $this->purchaser['name'] .
            $this->purchaser['surname'] .
            $this->purchaser['email'] .
            $now;

        $token = $this->public_key . ":" . base64_encode(sha1($hash_text, true));

        $params = [
            'orderId' => $this->order_id,
            'amount' => $this->amount,
            'cardOwnerName' => $this->card['owner_name'],
            'cardNumber' => $this->card['number'],
            'cardExpireMonth' => $this->card['expire_month'],
            'cardExpireYear' => $this->card['expire_year'],
            'installment' => $this->installment,
            'cardCvc' => $this->card['cvc'],
            'mode' => $this->mode,
            'purchaser' => [
                'name' => $this->purchaser['name'],
                'surname' => $this->purchaser['surname'],
                'email' => $this->purchaser['email'],
                'clientIp' => $this->purchaser['clientIp'],
            ],
            'products' => $this->products,
            'successUrl' => $this->success_url,
            'failureUrl' => $this->failure_url,
            'echo' => $this->echo,
            'version' => $this->version,
            'language' => 'tr-TR',
            'vendorId' => $this->vendor_id,
            'transactionDate' => $now,
            'token' => $token,
        ];

        $jsonParams = json_encode($params);

        return $this->toHtml($jsonParams, $this->three_d_url);
    }

    /**
     * İade Yapılabilirlik Kontrolü
     *
     * @param $params
     * @return false|array
     */
    protected function isRefundable($params)
    {
        // açık anahtar
        $this->public_key = $this->config['iparam_publickey'];

        // gizli anahtar
        $this->private_key = $this->config['iparam_privatekey'];

        $transactionDate = $this->now();
        $token = $this->public_key . ':' . base64_encode(sha1($this->private_key . $params['transid'] . $this->getClientIp() . $transactionDate, true));

        $data = [
            'clientIp' => $this->getClientIp(),
            'amount' => number_format($params['amount'], 2, '', ''),
            'orderId' => $params['transid']
        ];

        $dataString = json_encode($data);

        $ch = curl_init($this->refund_url_inquiry);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length:' . strlen($dataString),
            'token:' . $token,
            'transactionDate:' . $transactionDate,
            'version:' . '1.0',
        ));

        $response = curl_exec($ch);

        if (!is_null($response) and $response != false)
            return json_decode($response, true);
        else
            return false;
    }

    /**
     * İade İsteği
     *
     * @param $params
     * @return false|array
     */
    public function refund($params)
    {
        $refundable = $this->isRefundable($params);

        if (is_array($refundable) && $refundable['result'] && !isset($refundable['errorCode'])) {
            $transactionDate = $this->now();
            $token = $this->public_key . ':' . base64_encode(sha1($this->private_key . $params['transid'] . $this->getClientIp() . $transactionDate, true));

            $data = [
                'clientIp' => $this->getClientIp(),
                'amount' => number_format($params['amount'], 2, '', ''),
                'orderId' => $params['transid'],
                'refundHash' => $refundable['refundHash'],
            ];

            $dataString = json_encode($data);

            $ch = curl_init($this->refund_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length:' . strlen($dataString),
                'token:' . $token,
                'transactionDate:' . $transactionDate,
                'version:' . '1.0',
            ));

            $response = curl_exec($ch);

            if (!is_null($response) and $response != false)
                return json_decode($response, true);
            else
                return false;
        } else {
            return $refundable;
        }
    }

}