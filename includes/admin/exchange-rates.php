<?php

require_once dirname(__DIR__) . '/database.php';

function grinco_admin_exchange_rate($sourceCurrency, $destinationCurrency)
{
    $statement = grinco_database()->prepare(
        'SELECT taux FROM taux_change '
        . 'WHERE devise_source = :source AND devise_destination = :destination '
        . 'ORDER BY id DESC LIMIT 1'
    );
    $statement->execute(array(
        ':source' => strtoupper((string) $sourceCurrency),
        ':destination' => strtoupper((string) $destinationCurrency)
    ));
    $rate = $statement->fetchColumn();
    return $rate === false ? null : (float) $rate;
}

function grinco_admin_usd_cny_rate()
{
    return grinco_admin_exchange_rate('USD', 'CNY');
}

function grinco_admin_convert_usd_to_cny($priceUsd, $rate = false)
{
    if ($rate === false) {
        $rate = grinco_admin_usd_cny_rate();
    }
    if ($rate === null || !is_numeric($priceUsd) || (float) $priceUsd < 0) {
        return null;
    }
    return (float) $priceUsd * (float) $rate;
}

function grinco_admin_format_cny($amount)
{
    return number_format((float) $amount, 2, ',', ' ') . ' CNY';
}
