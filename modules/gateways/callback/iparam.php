<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../iparam/iparam.class.php';

App::load_function('gateway');
App::load_function('invoice');

$iParam = new iParam();

$gatewayParams = getGatewayVariables("iparam");

if (!$gatewayParams['type']) {
    header('HTTP/1.0 403 Forbidden');
    echo "Module not activated.";
    exit;
}

$data = file_get_contents('php://input');
$dataDecoded = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), true );

if (!isset($dataDecoded['data'])
    || !isset($dataDecoded['type'])
    || !isset($dataDecoded['id'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "Bad request.";
    exit;
}

if (!isset($dataDecoded['data']['orderId'])) {
    header('HTTP/1.0 400 Bad Request');
    echo "Bad request.";
    exit;
}

if ($dataDecoded['type'] == "payment.api.threed") {
    $paymentType = "3D";
} else if ($dataDecoded['type'] == "payment.api.auth") {
    $paymentType = "API";
} else {
    header('HTTP/1.0 400 Bad Request');
    echo "Bad request.";
    exit;
}

$result = $dataDecoded['data']['result'];
$orderId = $dataDecoded['data']['orderId'];
$invoiceId = explode("IPARAM", $orderId)[1];
$transactionId = $dataDecoded['id'];
$transactionDate = $dataDecoded['data']['createdAt'];
$transactionStatus = ($result == 1) ? 'Success' : 'Error';
$amount = $dataDecoded['data']['amount'];
$dataDecoded['extra'] = [
    'webhookIPAddress' => $iParam->getClientIp(),
    'webhookUserAgent' => $_SERVER['HTTP_USER_AGENT'],
];
$kdv = 1.20;

logTransaction($gatewayParams['name'], $dataDecoded, $transactionStatus);

if ($result == 1) {
    // İsteğe bağlı olarak ödemeyi onaylamadan önce vade farkını faturaya ekletebilirsiniz.

    /*$invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

    $invoiceAmount = $invoice['balance'] * 100;
    if ($amount > $invoiceAmount) {

        $commission = ($amount - $invoiceAmount) / 100;
        $commissionEx = number_format(($commission / $kdv), 2, '.', '');

        localAPI('UpdateInvoice', [
            'invoiceid' => $invoiceId,
            'status' => 'Unpaid',
            'newitemdescription' => [
                0 => 'Taksitli Ödeme Vade Farkı',
            ],
            'newitemamount' => [
                0 => $commissionEx,
            ],
            'newitemtaxed' => [
                0 => true,
            ]
        ]);
    }*/

    addInvoicePayment(
        $invoiceId,
        $orderId,
        '',
        '',
        "iparam"
    );
}

echo "Ok";
exit;