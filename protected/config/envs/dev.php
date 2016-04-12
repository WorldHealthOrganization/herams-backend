<?php
return [
    'components' => [
        'request' => [
            'hostInfo' => CONSOLE ? 'https://prime.projects.sam-it.eu' : null
        ],
        'db' => [
            'class' => \yii\db\Connection::class,
            'charset' => 'utf8',
            'dsn' => 'mysql:host=localhost;dbname=who;',
            'password' => 'z2P6NUSj3YfcfVH4',
            'username' => 'who',
            'enableSchemaCache' => true,
            'schemaCache' => 'cache',
            'enableQueryCache' => true,
            'queryCache' => 'cache',
            'tablePrefix' => 'prime2_',
            'on afterOpen' => function($event) {
                $event->sender->createCommand("SET SESSION sql_mode = strict_trans_tables;")->execute();
            }
        ]
    ]
];

