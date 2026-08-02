<?php

require_once __DIR__ . '/includes/form-security.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/catalogue-files.php';

grinco_apply_form_security_headers();

$documentId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) && (int) $_GET['id'] > 0
    ? (int) $_GET['id']
    : 0;
if ($documentId <= 0) {
    http_response_code(404);
    exit;
}

try {
    $statement = grinco_database()->prepare(
        'SELECT pd.document, p.reference FROM produit_documents pd '
        . 'INNER JOIN produits p ON p.id = pd.produit_id WHERE pd.id = :id LIMIT 1'
    );
    $statement->execute(array(':id' => $documentId));
    $document = $statement->fetch();
} catch (PDOException $exception) {
    error_log('[GRINCO public product document] Unable to load document.');
    http_response_code(503);
    exit;
}

$resolved = $document ? grinco_catalogue_resolve_stored_file($document['document'], 'document') : false;
if ($resolved === false || !$resolved['exists']) {
    http_response_code(404);
    exit;
}

$safeReference = preg_replace('/[^a-z0-9_-]+/i', '-', (string) $document['reference']);
$downloadName = 'document-' . trim($safeReference, '-') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($resolved['absolute_path']));
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
readfile($resolved['absolute_path']);
exit;
