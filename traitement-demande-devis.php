<?php
require_once __DIR__ . '/includes/devis-service.php';
require_once __DIR__ . '/includes/devis-notification.php';

grinco_apply_form_security_headers();
grinco_start_secure_session();

function quote_request_log($requestId, $message)
{
    error_log('[GRINCO multi-product quote][' . $requestId . '] ' . $message);
}

function quote_capture_input()
{
    $old = array();
    foreach (array('nom', 'entreprise', 'telephone', 'email', 'message') as $field) {
        $old[$field] = grinco_normalize_text(strip_tags(grinco_post_value($field)), $field === 'message');
    }
    $old['consent'] = grinco_post_value('consent') === '1' ? '1' : '';
    return $old;
}

function quote_clean_text($field, $label, $maximum, $multiline, &$errors, &$fieldErrors)
{
    $raw = grinco_post_value($field);
    $value = grinco_normalize_text(strip_tags($raw), $multiline);
    if (grinco_utf8_length($value) > $maximum) {
        $message = $label . ' dépasse la longueur autorisée.';
        $errors[] = $message;
        $fieldErrors[$field] = $message;
        $value = grinco_utf8_substr($value, 0, $maximum);
    }
    if (grinco_has_forbidden_control_characters($raw, $multiline)) {
        $message = $label . ' contient des caractères non autorisés.';
        $errors[] = $message;
        $fieldErrors[$field] = $message;
    }
    return $value;
}

function quote_fail($message, $errors, $fieldErrors)
{
    grinco_set_form_flash('quote', 'error', $message, $errors, $fieldErrors);
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

$requestLogId = substr(hash('sha256', uniqid('', true)), 0, 12);
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    quote_request_log($requestLogId, 'Invalid HTTP method.');
    quote_fail('Votre demande n’a pas pu être traitée.', array(), array());
}
if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 50000) {
    quote_request_log($requestLogId, 'Request body too large.');
    quote_fail('Votre demande n’a pas pu être traitée.', array(), array());
}

$_SESSION['quote_old'] = quote_capture_input();
$cart = grinco_quote_cart();
if (empty($cart)) {
    quote_request_log($requestLogId, 'Empty cart rejected.');
    grinco_quote_cart_set_flash('error', 'Votre sélection est vide.');
    header('Location: ' . grinco_url('/panier-devis'));
    exit;
}
if (!grinco_validate_csrf_token('quote', grinco_post_value('csrf_token'))) {
    quote_request_log($requestLogId, 'Invalid CSRF token.');
    quote_fail('Votre session a expiré. Veuillez recommencer.', array(), array());
}
$knownSubmissionToken = isset($_SESSION['quote_submission_token']) ? (string) $_SESSION['quote_submission_token'] : '';
if ($knownSubmissionToken === '' || !grinco_hash_equals($knownSubmissionToken, grinco_post_value('submission_token'))) {
    quote_request_log($requestLogId, 'Duplicate or invalid submission token.');
    quote_fail('Ce formulaire a déjà été envoyé ou n’est plus valide.', array(), array());
}
if (grinco_post_value('website') !== '' || grinco_post_value('company_url') !== '') {
    quote_request_log($requestLogId, 'Honeypot filled.');
    quote_fail('Votre demande n’a pas pu être traitée.', array(), array());
}
$timing = grinco_validate_form_timing('quote', grinco_post_value('form_started_at'));
if (!$timing['valid']) {
    quote_request_log($requestLogId, 'Invalid form timing: ' . $timing['reason']);
    quote_fail($timing['reason'] === 'submitted_too_fast' ? 'Veuillez attendre quelques secondes avant l’envoi.' : 'Votre session a expiré. Veuillez recommencer.', array(), array());
}
if (!grinco_validate_request_origin()) {
    quote_request_log($requestLogId, 'Foreign request origin.');
    quote_fail('Votre demande n’a pas pu être vérifiée.', array(), array());
}

$errors = array();
$fieldErrors = array();
$nameResult = grinco_validate_name(grinco_post_value('nom'), 2, 100);
foreach ($nameResult['errors'] as $error) {
    $errors[] = $error;
    $fieldErrors['nom'] = $error;
}
$phoneResult = grinco_validate_phone(grinco_post_value('telephone'), true);
foreach ($phoneResult['errors'] as $error) {
    $errors[] = $error;
    $fieldErrors['telephone'] = $error;
}
$email = trim(grinco_post_value('email'));
if ($email !== '' && (strlen($email) > 150 || grinco_detect_header_injection($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    $fieldErrors['email'] = 'L’adresse e-mail n’est pas valide.';
    $errors[] = $fieldErrors['email'];
}
$data = array(
    'nom' => $nameResult['value'],
    'entreprise' => quote_clean_text('entreprise', 'Le nom de l’entreprise', 150, false, $errors, $fieldErrors),
    'telephone' => $phoneResult['value'],
    'email' => $email,
    'message' => quote_clean_text('message', 'Le message', 5000, true, $errors, $fieldErrors)
);
if (grinco_post_value('consent') !== '1') {
    $fieldErrors['consent'] = 'Vous devez accepter l’utilisation de vos informations.';
    $errors[] = $fieldErrors['consent'];
}
if (!empty($errors)) {
    quote_request_log($requestLogId, 'Client validation failed.');
    quote_fail('Certaines informations sont invalides.', $errors, $fieldErrors);
}

$spam = grinco_analyze_spam('quote', array(
    'name' => $data['nom'],
    'company' => $data['entreprise'],
    'message' => $data['message']
), '');
if ($spam['rejected']) {
    quote_request_log($requestLogId, 'Spam analysis rejected the request.');
    quote_fail('Votre demande n’a pas pu être traitée.', array(), array());
}
$rate = grinco_check_rate_limit('quote', grinco_client_ip(), $email);
if (!$rate['allowed']) {
    quote_request_log($requestLogId, 'Rate limit rejected the request.');
    quote_fail('Plusieurs tentatives ont été détectées. Veuillez patienter.', array(), array());
}
$turnstile = grinco_validate_turnstile(grinco_post_value('cf-turnstile-response'), grinco_client_ip());
if (!$turnstile['valid']) {
    quote_request_log($requestLogId, 'Turnstile validation failed.');
    quote_fail('La vérification de sécurité a échoué.', array(), array());
}

try {
    $created = grinco_quote_create_request($data, $cart);
} catch (Exception $exception) {
    quote_request_log($requestLogId, 'Transactional database operation failed: ' . get_class($exception));
    quote_fail('La demande ne peut pas être enregistrée pour le moment. Votre sélection a été conservée.', array(), array());
}

grinco_quote_cart_clear();
unset($_SESSION['quote_old'], $_SESSION['quote_submission_token']);
grinco_finalize_form_attempt('quote');
$confirmationToken = grinco_random_token();
$_SESSION['quote_confirmation'] = array(
    'token' => $confirmationToken,
    'request_id' => (int) $created['id']
);

$mailRequest = array(
    'id' => (int) $created['id'],
    'nom' => $data['nom'],
    'entreprise' => $data['entreprise'],
    'telephone' => $data['telephone'],
    'email' => $data['email'],
    'message' => $data['message'],
    'date_demande' => $created['date_demande']
);
try {
    grinco_quote_send_notification($mailRequest, $created['products']);
    quote_request_log($requestLogId, 'Request committed and SMTP notification sent.');
} catch (Exception $exception) {
    quote_request_log($requestLogId, 'Request committed; SMTP notification failed: ' . get_class($exception));
}

header('Location: ' . grinco_url('/confirmation-devis') . '?reference=' . rawurlencode($confirmationToken));
exit;
