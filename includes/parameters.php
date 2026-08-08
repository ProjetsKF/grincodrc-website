<?php

require_once __DIR__ . '/database.php';

function grinco_parameters_get()
{
    $statement = grinco_database()->prepare(
        'SELECT id, nom_entreprise, email, telephone, adresse '
        . 'FROM parametres ORDER BY id ASC LIMIT 1'
    );
    $statement->execute();
    $parameters = $statement->fetch();
    if (!$parameters) {
        return null;
    }

    $parameters['id'] = (int) $parameters['id'];
    foreach (array('email', 'telephone', 'adresse') as $field) {
        $parameters[$field] = $parameters[$field] === null ? '' : (string) $parameters[$field];
    }
    return $parameters;
}
