<?php
/**
 * WHMCS iPara Gateway Module
 *
 * @author Gürkan Biçer <bicergurkan@gmail.com>
 * @author Veridyen <info@veridyen.com>
 * @link https://www.veridyen.com/
 * @see https://dev.ipara.com.tr/
 * @see https://developers.whmcs.com/payment-gateways/
 * @version 1.0
 *
 * WHMCS iPara ödeme modülünü ücretsiz olarak kullanabilir, ihtiyaçlarınız doğrultusunda değiştirebilirsiniz. 
 * Teknik destek için WHMCS'nin ve iPara'nın yardım dökümanlarına gözatabilirisiniz.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/iparam/iparam.class.php';

function iparam_MetaData()
{
    return [
        'DisplayName' => 'iPara Ödeme Modülü',
        'APIVersion' => '1',
		'DisableLocalCredtCardInput' => false,
		'TokenisedStorage' => false,
    ];
}

function iparam_config()
{
    $config = [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'iPara Ödeme Modülü'
        ],
        'iparam_publickey' => [
            'FriendlyName' => 'Mağaza Açık Anahtar (public key)',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'ipara.com.tr mağaza ayarlarınızdan öğrenebilirsiniz.',
        ],
        'iparam_privatekey' => [
            'FriendlyName' => 'Mağaza Kapalı Anahtar (private key)',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'ipara.com.tr mağaza ayarlarınızdan öğrenebilirsiniz.',
        ],
        'iparam_3dmode' => [
            'FriendlyName' => '3D Secure Modu',
            'Type' => 'dropdown',
            'Options' => [
                'on' => 'Tüm ödemelerde 3D secure zorunlu olsun.',
                'off' => 'Tüm ödemeleri API ile yaptır.',
            ],
            'Description' => 'Ödeme işleminde 3D Secure zorunluluğunu belirleyiniz.',
        ],
        'iparam_testmode' => [
            'FriendlyName' => 'Mağaza Modu',
            'Type' => 'dropdown',
            'Options' => [
                'T' => 'Test Ortamı',
                'P' => 'Gerçek Ortam',
            ],
            'Description' => 'Mağaza modunu belirleyiniz.',
        ],
    ];
    
    for ($i = 1; $i <= 12; $i++) {
		$config["iparam_{$i}_installment"] = [
			'FriendlyName' => ($i == 1 ? 'Tek çekim' : $i . ' Taksit') . ' Komisyonu',
			'Type' => 'text',
			'Size' => '4',
			'Default' => $i + (0.4) + ($i / 2),
			'Description' => '',
		];
	}
    
    return $config;
}

function iparam_capture($params)
{
    $iParam = new iParam();
    $response = $iParam->capture($params, $_POST);
}

function iparam_refund($params)
{
    $iParam = new iParam();
    $response = $iParam->refund($params);

    if ($response && $response['result']) {
        return [
            'status' => 'success',
            'rawdata' => $response,
            'transid' => $params['transid'],
            'fees' => 0,
        ];
    } else {
        return [
            'status' => 'error',
            'rawdata' => $response,
            'transid' => $params['transid'],
            'fees' => 0,
        ];
    }
}
