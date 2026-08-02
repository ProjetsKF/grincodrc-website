<?php

/*
 * Configuration MySQL du catalogue GRINCO.
 *
 * En production, les valeurs doivent être fournies par l'environnement PHP.
 * Les valeurs par défaut correspondent uniquement à l'installation EasyPHP
 * locale actuelle.
 */
$databaseHost = getenv('GRINCO_DB_HOST');
$databasePort = getenv('GRINCO_DB_PORT');
$databaseName = getenv('GRINCO_DB_NAME');
$databaseUser = getenv('GRINCO_DB_USER');
$databasePassword = getenv('GRINCO_DB_PASSWORD');

return array(
    'host' => $databaseHost === false || trim($databaseHost) === '' ? '127.0.0.1' : trim($databaseHost),
    'port' => $databasePort === false || trim($databasePort) === '' ? '3306' : trim($databasePort),
    'name' => $databaseName === false || trim($databaseName) === '' ? 'grinco_catalogue' : trim($databaseName),
    'user' => $databaseUser === false || trim($databaseUser) === '' ? 'root' : trim($databaseUser),
    'password' => $databasePassword === false ? '' : (string) $databasePassword,
    'charset' => 'utf8mb4'
);
