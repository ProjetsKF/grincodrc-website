<?php

$turnstileSiteKey = getenv('TURNSTILE_SITE_KEY');
$turnstileSecretKey = getenv('TURNSTILE_SECRET_KEY');
$securityStoragePath = getenv('GRINCO_FORM_SECURITY_PATH');

return array(
    'timing' => array(
        'minimum_seconds' => 3,
        'maximum_seconds' => 7200
    ),
    'rate_limits' => array(
        'minute' => array('window' => 60, 'maximum' => 1),
        'hour' => array('window' => 3600, 'maximum' => 5),
        'day' => array('window' => 86400, 'maximum' => 10)
    ),
    'spam' => array(
        'reject_score' => 5,
        'maximum_urls' => array(
            'contact' => 1,
            'quote' => 1
        ),
        'risky_domains' => array(
            'mega.nz',
            'telegra.ph',
            'bit.ly',
            'tinyurl.com',
            't.me'
        ),
        'suspicious_email_domains' => array(
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'temp-mail.org',
            'yopmail.com'
        ),
        'violent_keywords' => array(
            'appel aux armes',
            'appel à la violence',
            'assassiner',
            'attack people',
            'attaquer des personnes',
            'bomb attack',
            'exterminate',
            'kill people',
            'kill them',
            'massacre',
            'menace de mort',
            'tuer des personnes',
            'violent attack'
        ),
        'campaign_keywords' => array(
            'join our movement',
            'political propaganda',
            'propaganda campaign',
            'telegram channel',
            'mass distribution',
            'diffusez massivement'
        )
    ),
    'allowed_hosts' => array(
        'grincodrc.com',
        'www.grincodrc.com',
        'localhost',
        '127.0.0.1'
    ),
    'turnstile' => array(
        'enabled' => $turnstileSiteKey !== false
            && trim($turnstileSiteKey) !== ''
            && $turnstileSecretKey !== false
            && trim($turnstileSecretKey) !== '',
        'site_key' => $turnstileSiteKey === false ? '' : trim($turnstileSiteKey),
        'secret_key' => $turnstileSecretKey === false ? '' : trim($turnstileSecretKey),
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        'timeout_seconds' => 8
    ),
    'storage' => array(
        'path' => $securityStoragePath === false ? '' : trim($securityStoragePath),
        'log_retention_days' => 30,
        'rate_retention_seconds' => 86400
    )
);
