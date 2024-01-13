<?php

$config = [

	'app' => 'appname',
	'emailLoginEnabled' => true,
	'template' => 'standard-login-template',
    'emailSenderAddress' => 'info@domain.com',
    'emailSenderName' => 'Your name',
	
	'dbHost' => '...',
	'dbName' => '...',
	'dbUser' => '...',
	'dbPassword' => '...',

    'providers' => [

        'Facebook' => [
			'enabled' => true, 
			'keys' => [
				'id' => '...', 
				'secret' => '...'
			]
		],
		
        'Google' => [
            'enabled' => true, 
            'keys' => [
                'id' => '...', 
                'secret' => '...'
            ]
        ],

        'Instagram' => [
			'enabled' => true, 
			'keys' => [
				'id' => '...', 
				'secret' => '...'
			]
        ],
		
        'Twitter' => [
            'enabled' => true,
            'keys' => [
                'key' => '...',
                'secret' => '...'
            ]
        ],

        'Amazon' => [
            'enabled' => false, 
            'keys' => [
                'id' => '...', 
                'secret' => '...'
            ]
        ],

    ]

];
