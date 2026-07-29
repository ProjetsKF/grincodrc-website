<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function contact_redirect()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Location: contact.php');
    exit;
}

function contact_set_flash($type, $message, $errors)
{
    unset($_SESSION['contact_success'], $_SESSION['contact_error'], $_SESSION['contact_validation_errors']);

    if ($type === 'success') {
        $_SESSION['contact_success'] = $message;
    } else {
        $_SESSION['contact_error'] = $message;
    }

    if (is_array($errors) && !empty($errors)) {
        $_SESSION['contact_validation_errors'] = array_values($errors);
    }
}

function contact_log($message)
{
    global $contactRequestId;
    error_log('[GRINCO contact form][' . $contactRequestId . '] ' . $message);
}

function contact_post_value($name)
{
    if (!isset($_POST[$name]) || !is_scalar($_POST[$name])) {
        return '';
    }

    return (string) $_POST[$name];
}

function contact_capture_old_input()
{
    $fields = array('full_name', 'email', 'phone', 'subject', 'message');
    $oldInput = array();

    foreach ($fields as $field) {
        $oldInput[$field] = trim(strip_tags(contact_post_value($field)));
    }

    return $oldInput;
}

function contact_clean_field($name, $label, $maxLength, $multiline, &$errors)
{
    $value = strip_tags(contact_post_value($name));
    $value = preg_replace('/\r\n?/', "\n", $value);

    if ($multiline) {
        $value = preg_replace('/[ \t]+/u', ' ', $value);
        $value = preg_replace('/\n{3,}/u', "\n\n", $value);
    } else {
        $value = preg_replace('/\s+/u', ' ', $value);
    }

    $value = trim($value);

    if (mb_strlen($value, 'UTF-8') > $maxLength) {
        $errors[] = $label . ' dépasse la longueur autorisée.';
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    return $value;
}

function contact_has_header_injection($value)
{
    return preg_match('/[\r\n]/', $value) === 1;
}

function contact_tokens_match($knownToken, $receivedToken)
{
    if (function_exists('hash_equals')) {
        return hash_equals($knownToken, $receivedToken);
    }

    if (strlen($knownToken) !== strlen($receivedToken)) {
        return false;
    }

    $difference = 0;
    $length = strlen($knownToken);
    for ($index = 0; $index < $length; $index++) {
        $difference |= ord($knownToken[$index]) ^ ord($receivedToken[$index]);
    }

    return $difference === 0;
}

function contact_email_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function contact_email_value($value)
{
    return $value === ''
        ? '<span style="color:#8A949E;">Non renseigné</span>'
        : nl2br(contact_email_escape($value));
}

function contact_email_row($label, $value)
{
    return '<tr>'
        . '<td width="34%" valign="top" style="padding:11px 14px;border-bottom:1px solid #E8ECE9;color:#5F6B76;font-size:13px;font-weight:600;">' . contact_email_escape($label) . '</td>'
        . '<td valign="top" style="padding:11px 14px;border-bottom:1px solid #E8ECE9;color:#1F2933;font-size:14px;line-height:1.55;">' . contact_email_value($value) . '</td>'
        . '</tr>';
}

function contact_whatsapp_url($phone)
{
    $trimmedPhone = trim($phone);
    $digits = preg_replace('/\D+/', '', $trimmedPhone);

    if ($digits === '') {
        return '';
    }

    if (substr($digits, 0, 1) === '0' && strlen($digits) >= 9 && strlen($digits) <= 10) {
        $digits = '243' . substr($digits, 1);
    } elseif (substr($digits, 0, 3) !== '243' && substr($trimmedPhone, 0, 1) !== '+') {
        return '';
    }

    if (strlen($digits) < 8 || strlen($digits) > 15) {
        return '';
    }

    return 'https://wa.me/' . $digits;
}

function contact_client_ip()
{
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        return (string) $_SERVER['REMOTE_ADDR'];
    }

    return '';
}

$contactRequestId = substr(sha1(uniqid('', true)), 0, 12);

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    contact_log('Requête refusée : méthode HTTP différente de POST.');
    contact_set_flash('error', 'Cette action nécessite l’envoi du formulaire de contact.', array());
    contact_redirect();
}

contact_log('Requête POST reçue.');

if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 30000) {
    contact_log('Validation refusée : taille de requête supérieure à 30 000 octets.');
    contact_set_flash('error', 'Votre message est trop volumineux. Veuillez en réduire la longueur.', array());
    contact_redirect();
}

$_SESSION['contact_old'] = contact_capture_old_input();

$receivedToken = contact_post_value('csrf_token');
$knownToken = isset($_SESSION['contact_csrf_token']) ? (string) $_SESSION['contact_csrf_token'] : '';

if ($receivedToken === '' || $knownToken === '' || !contact_tokens_match($knownToken, $receivedToken)) {
    contact_log('Validation refusée : jeton CSRF absent ou invalide.');
    contact_set_flash('error', 'Votre session a expiré. Veuillez recharger la page et recommencer.', array());
    contact_redirect();
}

if (contact_post_value('website') !== '') {
    contact_log('Soumission neutralisée : honeypot rempli.');
    unset($_SESSION['contact_old']);
    $_SESSION['contact_csrf_token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(32))
        : bin2hex(openssl_random_pseudo_bytes(32));
    contact_set_flash('success', 'Votre message a été envoyé avec succès. Notre équipe vous répondra dans les meilleurs délais.', array());
    contact_redirect();
}

$formStartedAt = isset($_SESSION['contact_form_started_at']) ? (int) $_SESSION['contact_form_started_at'] : 0;
if ($formStartedAt <= 0 || (time() - $formStartedAt) < 3) {
    contact_log('Validation refusée : délai minimal de trois secondes non respecté.');
    contact_set_flash('error', 'Veuillez attendre quelques secondes avant d’envoyer le formulaire.', array());
    contact_redirect();
}

$now = time();
$attempts = isset($_SESSION['contact_submission_attempts']) && is_array($_SESSION['contact_submission_attempts'])
    ? $_SESSION['contact_submission_attempts']
    : array();
$recentAttempts = array();

foreach ($attempts as $attemptTimestamp) {
    if (is_numeric($attemptTimestamp) && (int) $attemptTimestamp > ($now - 900)) {
        $recentAttempts[] = (int) $attemptTimestamp;
    }
}

if (count($recentAttempts) >= 5) {
    contact_log('Validation refusée : limitation de cinq tentatives sur quinze minutes atteinte.');
    contact_set_flash('error', 'Vous avez effectué trop de tentatives. Veuillez réessayer dans quinze minutes.', array());
    contact_redirect();
}

$errors = array();
$rawName = contact_post_value('full_name');
$rawEmail = contact_post_value('email');
$rawSubject = contact_post_value('subject');

if (
    contact_has_header_injection($rawName)
    || contact_has_header_injection($rawEmail)
    || contact_has_header_injection($rawSubject)
) {
    $errors[] = 'Certaines informations contiennent des caractères non autorisés.';
}

$data = array(
    'full_name' => contact_clean_field('full_name', 'Le nom complet', 120, false, $errors),
    'email' => trim($rawEmail),
    'phone' => contact_clean_field('phone', 'Le numéro de téléphone', 40, false, $errors),
    'subject' => contact_clean_field('subject', 'Le sujet', 160, false, $errors),
    'message' => contact_clean_field('message', 'Le message', 5000, true, $errors)
);

if ($data['full_name'] === '' || mb_strlen($data['full_name'], 'UTF-8') < 2) {
    $errors[] = 'Veuillez indiquer votre nom complet.';
}
if ($data['email'] === '') {
    $errors[] = 'L’adresse e-mail est obligatoire.';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'L’adresse e-mail n’est pas valide.';
}
if ($data['subject'] === '' || mb_strlen($data['subject'], 'UTF-8') < 3) {
    $errors[] = 'Veuillez indiquer le sujet de votre message.';
}
if ($data['message'] === '' || mb_strlen($data['message'], 'UTF-8') < 10) {
    $errors[] = 'Veuillez saisir un message d’au moins dix caractères.';
}

$phonePattern = '/^[0-9+().\/\s-]+$/';
if (
    $data['phone'] !== ''
    && (
        !preg_match($phonePattern, $data['phone'])
        || strlen(preg_replace('/\D+/', '', $data['phone'])) < 6
        || strlen(preg_replace('/\D+/', '', $data['phone'])) > 15
    )
) {
    $errors[] = 'Le numéro de téléphone n’est pas valide.';
}

$_SESSION['contact_old'] = $data;

if (!empty($errors)) {
    contact_log('Validation refusée : un ou plusieurs champs sont invalides.');
    contact_set_flash(
        'error',
        'Veuillez compléter correctement les champs signalés.',
        array_values(array_unique($errors))
    );
    contact_redirect();
}

contact_log('Validation réussie.');
$recentAttempts[] = $now;
$_SESSION['contact_submission_attempts'] = $recentAttempts;

try {
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    $phpMailerSourcePath = __DIR__ . '/vendor/phpmailer/phpmailer/src';
    $manualFiles = array(
        $phpMailerSourcePath . '/Exception.php',
        $phpMailerSourcePath . '/PHPMailer.php',
        $phpMailerSourcePath . '/SMTP.php'
    );
    $configPath = __DIR__ . '/config/mail.php';

    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    } elseif (is_file($manualFiles[0]) && is_file($manualFiles[1]) && is_file($manualFiles[2])) {
        require_once $manualFiles[0];
        require_once $manualFiles[1];
        require_once $manualFiles[2];
    } else {
        throw new \RuntimeException('Les fichiers requis de PHPMailer sont introuvables.');
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        throw new \RuntimeException('La classe PHPMailer est indisponible.');
    }

    contact_log('PHPMailer chargé correctement.');

    if (!is_file($configPath)) {
        throw new \RuntimeException('Le fichier de configuration SMTP est introuvable.');
    }

    $mailConfig = require $configPath;
    if (!is_array($mailConfig)) {
        throw new \RuntimeException('La configuration SMTP est invalide.');
    }

    $requiredConfig = array('host', 'port', 'username', 'password', 'encryption', 'from_email', 'from_name', 'recipient_email');
    foreach ($requiredConfig as $configKey) {
        if (!isset($mailConfig[$configKey]) || trim((string) $mailConfig[$configKey]) === '') {
            throw new \RuntimeException('Un paramètre SMTP obligatoire reste à configurer : ' . $configKey);
        }
    }

    date_default_timezone_set('Africa/Lubumbashi');
    $receivedAt = date('d/m/Y à H:i');
    $clientIp = contact_client_ip();
    $safeSubject = preg_replace('/[\r\n]+/', ' ', $data['subject']);
    $mailSubject = 'Nouveau message depuis le site GRINCO RDC – ' . $safeSubject;
    $whatsappUrl = contact_whatsapp_url($data['phone']);

    $visitorRows = ''
        . contact_email_row('Nom complet', $data['full_name'])
        . contact_email_row('E-mail', $data['email'])
        . contact_email_row('Téléphone', $data['phone'])
        . contact_email_row('Sujet', $data['subject'])
        . contact_email_row('Date et heure', $receivedAt)
        . contact_email_row('Adresse IP', $clientIp);

    $whatsappButton = '';
    if ($whatsappUrl !== '') {
        $whatsappButton = '<td style="padding-left:10px;">'
            . '<a href="' . contact_email_escape($whatsappUrl) . '" style="display:inline-block;padding:13px 18px;border:1px solid #3A884C;border-radius:6px;color:#3A884C;font-family:Arial,sans-serif;font-size:13px;font-weight:700;text-decoration:none;">Contacter par WhatsApp</a>'
            . '</td>';
    }

    $emailBody = '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Nouveau message GRINCO RDC</title></head>'
        . '<body style="margin:0;padding:0;background:#F4F6F5;font-family:Arial,Helvetica,sans-serif;color:#1F2933;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#F4F6F5;padding:30px 10px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:700px;background:#FFFFFF;border-top:6px solid #3A884C;border-bottom:6px solid #CF2E2E;border-radius:12px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,0.08);">'
        . '<tr><td style="padding:28px 30px 22px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td><div style="color:#3A884C;font-size:25px;font-weight:700;">GRINCO RDC</div><div style="margin-top:8px;color:#5F6B76;font-size:13px;">Formulaire de contact du site web</div></td>'
        . '<td align="right"><span style="display:inline-block;padding:8px 13px;border-radius:20px;background:#FCEAEA;color:#CF2E2E;font-size:12px;font-weight:700;text-transform:uppercase;">Nouveau message</span></td>'
        . '</tr></table></td></tr>'
        . '<tr><td style="padding:0 30px 24px;"><div style="padding:17px 18px;border-radius:8px;background:#EAF4EC;">'
        . '<strong style="font-size:16px;">Message reçu depuis le formulaire de contact</strong></div></td></tr>'
        . '<tr><td style="padding:0 30px 26px;"><div style="margin-bottom:10px;color:#3A884C;font-size:12px;font-weight:700;letter-spacing:1px;">INFORMATIONS DU VISITEUR</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #E8ECE9;border-radius:8px;border-collapse:separate;overflow:hidden;">' . $visitorRows . '</table></td></tr>'
        . '<tr><td style="padding:0 30px 26px;"><div style="margin-bottom:10px;color:#3A884C;font-size:12px;font-weight:700;letter-spacing:1px;">MESSAGE</div>'
        . '<div style="padding:18px;border:1px solid #E8ECE9;border-radius:8px;color:#1F2933;font-size:14px;line-height:1.7;">' . nl2br(contact_email_escape($data['message'])) . '</div></td></tr>'
        . '<tr><td style="padding:0 30px 30px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td><a href="mailto:' . contact_email_escape($data['email']) . '" style="display:inline-block;padding:14px 20px;border-radius:6px;background:#3A884C;color:#FFFFFF;font-size:13px;font-weight:700;text-decoration:none;">Répondre par e-mail</a></td>'
        . $whatsappButton
        . '</tr></table></td></tr>'
        . '<tr><td style="padding:22px 30px;background:#F7F9F8;border-top:1px solid #E8ECE9;color:#5F6B76;font-size:12px;line-height:1.6;text-align:center;">'
        . 'Ce message a été envoyé depuis le formulaire de contact du site GRINCO RDC.<br><a href="mailto:info@grincodrc.com" style="color:#3A884C;font-weight:700;text-decoration:none;">info@grincodrc.com</a>'
        . '</td></tr></table></td></tr></table></body></html>';

    $altBody = "GRINCO RDC - Nouveau message depuis le site\n"
        . "Date et heure : " . $receivedAt . "\n"
        . "Nom complet : " . $data['full_name'] . "\n"
        . "E-mail : " . $data['email'] . "\n"
        . "Téléphone : " . $data['phone'] . "\n"
        . "Sujet : " . $data['subject'] . "\n"
        . "Adresse IP : " . ($clientIp === '' ? 'Non disponible' : $clientIp) . "\n\n"
        . "MESSAGE\n" . $data['message'] . "\n";

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $mailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $mailConfig['username'];
    $mail->Password = (string) $mailConfig['password'];
    $mail->Port = (int) $mailConfig['port'];
    $mail->Timeout = 20;
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $encryption = strtolower(trim((string) $mailConfig['encryption']));
    if ($encryption === 'ssl' || $encryption === 'smtps') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls' || $encryption === 'starttls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom((string) $mailConfig['from_email'], (string) $mailConfig['from_name']);
    $mail->addAddress((string) $mailConfig['recipient_email']);
    $mail->addReplyTo($data['email'], $data['full_name']);
    $mail->isHTML(true);
    $mail->Subject = $mailSubject;
    $mail->Body = $emailBody;
    $mail->AltBody = $altBody;

    contact_log('Tentative de connexion et d’envoi SMTP.');
    $mail->send();
    contact_log('Envoi SMTP réussi.');

    unset($_SESSION['contact_old'], $_SESSION['contact_form_started_at']);
    $_SESSION['contact_csrf_token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(32))
        : bin2hex(openssl_random_pseudo_bytes(32));
    contact_set_flash(
        'success',
        'Votre message a été envoyé avec succès. Notre équipe vous répondra dans les meilleurs délais.',
        array()
    );
} catch (\Exception $exception) {
    contact_log('Échec du traitement ou de l’envoi SMTP : ' . $exception->getMessage());
    contact_set_flash(
        'error',
        'Votre message n’a pas pu être envoyé. Veuillez réessayer ou nous contacter directement à info@grincodrc.com.',
        array()
    );
}

contact_redirect();
