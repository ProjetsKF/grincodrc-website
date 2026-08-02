<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

$receivedCsrfToken = isset($_POST['csrf_token']) && is_scalar($_POST['csrf_token'])
    ? (string) $_POST['csrf_token']
    : '';

if (!grinco_validate_csrf_token('admin_logout', $receivedCsrfToken) || !grinco_validate_request_origin()) {
    http_response_code(403);
    exit('La demande n’a pas pu être vérifiée.');
}

grinco_admin_logout();
header('Location: ' . grinco_url('/connexion'));
exit;
