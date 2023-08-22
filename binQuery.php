<?php

use WHMCS\Database\Capsule;

require_once 'init.php';

$domains = [
    'alanadiniz.com'
];

// Referer kontrolü
$refererPass = 0;

foreach ($domains as $domain) {
    if (stristr($_SERVER['HTTP_REFERER'], $domain)) {
        $refererPass++;
        break;
    }
}

if ($refererPass == 0) {
    header('HTTP/1.0 403 Forbidden');
    echo "Forbidden.";
    exit;
}

// XHR kontrolü
if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('HTTP/1.0 403 Forbidden');
    echo "Forbidden.";
    exit;
}

$module = $_POST['m'];
$amount = (double) $_POST['amount'];
$cc = str_ireplace(' ', '', $_POST['cc']);

if ($module == "iparam") {
    require_once "modules/gateways/iparam/iparam.class.php";

    $iParam = new iParam();
    $iParamConfig = $iParam->config();
    $iParam->public_key = $iParamConfig['iparam_publickey'];
    $iParam->private_key = $iParamConfig['iparam_privatekey'];
    $binLookup = $iParam->binLookup($cc, $amount);

    if (!$binLookup) {
        echo '<script>console.log("BIN sorgulama çalışmıyor.");</script>';
        exit;
    } else {
        $installments = [];

        $installments[] = [
            'installment' => 1,
            'amount' => $amount,
            'text' => 'Tek Çekim',
        ];

        foreach ($binLookup['installmentDetail'] as $installment) {
            if ($installment['installment'] == 1) {
                continue;
            } else {
                $priceM = number_format((((100 + $iParamConfig['iparam_' . $installment['installment'] . '_installment']) * $amount) / 100) / $installment['installment'], 2, '.', '');
                $priceT = number_format((((100 + $iParamConfig['iparam_' . $installment['installment'] . '_installment']) * $amount) / 100), 2, '.', '');

                $installments[] = [
                    'installment' => $installment['installment'],
                    'amount' => $priceT,
                    'text' => $installment['installment'] . " Taksit &times; $priceM TL - Toplam: $priceT TL",
                ];
            }
        }

        $html = '';

        if (!empty($installments)) {
            $html  = '<label for="iparam_installment" class="form-label mb-2 font-weight-medium" style="font-size: 16px !important">Taksit Seçimi</label>';
            $html .= '<select name="iparam_installment" class="form-control rounded-0" id="iparam_installment">';

            foreach ($installments as $input) {
                $html .= '<option value="' . $input['installment'] . '">' . $input['text'] . '</option>';
            }

            $html .= '</select>';
        }

        ob_start();
        echo $html;
        ob_end_flush();
        exit;
    }
} else {
    echo '<script>console.log("Hatalı ödeme metodu.");</script>';
    exit;
}