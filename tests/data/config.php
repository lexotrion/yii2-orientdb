<?php

/**
 * This is the configuration file for the 'yii2-mongodb' unit tests.
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 */
$config = [
    'orientdb' => [
        'hostname'   => 'localhost',
        'port'       => 2424,
        'connection' => [
            'database' => 'GratefulDeadConcerts',
            'username' => 'root',
            'password' => '1',
        ],
        'options'    => [
            'databaseType'      => 'graph',
            'serializationType' => 'ORecordDocument2csv',
        ],
    ]
];

if (is_file(__DIR__ . '/config.local.php')) {
    include(__DIR__ . '/config.local.php');
}

return $config;
