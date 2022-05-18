<?php

return [

    'name'              => 'AamarPay Standard',
    'description'       => 'Enable the standard payment option of AamarPay',

    'form' => [
        'storeId'       => 'Store ID',
        'SignatureKey'  => 'Signature Key',
        'mode'          => 'Mode',
        'debug'         => 'Debug',
        'transaction'   => 'Transaction',
        'customer'      => 'Show to Customer',
        'order'         => 'Order',
    ],

    'test_mode'         => 'Warning: The payment gateway is in \'Sandbox Mode\'. Your account will not be charged.',
    //'description'       => 'Pay with PAYPAL',

];
