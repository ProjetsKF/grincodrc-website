<?php

function grinco_database_config()
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/database.php';
    }

    return $config;
}

function grinco_database()
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = grinco_database_config();
    $dsn = 'mysql:host=' . $config['host']
        . ';port=' . $config['port']
        . ';dbname=' . $config['name']
        . ';charset=' . $config['charset'];

    $connection = new PDO(
        $dsn,
        $config['user'],
        $config['password'],
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    return $connection;
}
