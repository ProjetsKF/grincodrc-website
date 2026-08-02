<?php

require_once __DIR__ . '/form-security.php';
require_once __DIR__ . '/database.php';

function grinco_admin_bootstrap()
{
    grinco_apply_form_security_headers();

    if (!headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    grinco_start_secure_session();
}

function grinco_admin_session_keys()
{
    return array(
        'grinco_admin_id',
        'grinco_admin_name',
        'grinco_admin_email',
        'grinco_admin_authenticated_at',
        'grinco_admin_last_activity',
        'grinco_admin_user_agent'
    );
}

function grinco_admin_clear_session()
{
    foreach (grinco_admin_session_keys() as $key) {
        unset($_SESSION[$key]);
    }
}

function grinco_admin_is_authenticated()
{
    if (
        empty($_SESSION['grinco_admin_id'])
        || empty($_SESSION['grinco_admin_email'])
        || empty($_SESSION['grinco_admin_authenticated_at'])
        || empty($_SESSION['grinco_admin_last_activity'])
    ) {
        return false;
    }

    $now = time();
    $maximumIdleSeconds = 1800;
    $maximumSessionSeconds = 28800;
    $userAgentHash = grinco_request_user_agent_hash();
    $knownUserAgentHash = isset($_SESSION['grinco_admin_user_agent'])
        ? (string) $_SESSION['grinco_admin_user_agent']
        : '';

    if (
        ($now - (int) $_SESSION['grinco_admin_last_activity']) > $maximumIdleSeconds
        || ($now - (int) $_SESSION['grinco_admin_authenticated_at']) > $maximumSessionSeconds
        || ($knownUserAgentHash !== '' && !grinco_hash_equals($knownUserAgentHash, $userAgentHash))
    ) {
        grinco_admin_clear_session();
        return false;
    }

    $_SESSION['grinco_admin_last_activity'] = $now;
    return true;
}

function grinco_admin_login($administrator)
{
    session_regenerate_id(true);

    $_SESSION['grinco_admin_id'] = (int) $administrator['id'];
    $_SESSION['grinco_admin_name'] = (string) $administrator['nom'];
    $_SESSION['grinco_admin_email'] = (string) $administrator['email'];
    $_SESSION['grinco_admin_authenticated_at'] = time();
    $_SESSION['grinco_admin_last_activity'] = time();
    $_SESSION['grinco_admin_user_agent'] = grinco_request_user_agent_hash();

    grinco_regenerate_csrf_token('admin_login');
    grinco_regenerate_csrf_token('admin_logout');
    grinco_regenerate_csrf_token('admin_password');
}

function grinco_admin_require_authentication()
{
    if (grinco_admin_is_authenticated()) {
        return;
    }

    $_SESSION['grinco_admin_login_notice'] = 'Veuillez vous connecter pour accéder à l’administration.';
    header('Location: ' . grinco_url('/connexion'));
    exit;
}

function grinco_admin_logout()
{
    grinco_admin_clear_session();
    $_SESSION = array();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function grinco_admin_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
