<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/includes/form-security.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

function contact_log($message)
{
    global $contactRequestId;
    error_log('[GRINCO contact form][' . $contactRequestId . '] ' . $message);
}

function contact_capture_old_input()
{
    $fields = array('full_name', 'email', 'phone', 'subject', 'message');
    $oldInput = array();

    foreach ($fields as $field) {
        $oldInput[$field] = grinco_normalize_text(strip_tags(grinco_post_value($field)), $field === 'message');
    }

    return $oldInput;
}

function contact_add_field_errors(&$errors, &$fieldErrors, $field, $newErrors)
{
    foreach ($newErrors as $newError) {
        $errors[] = $newError;
        if (empty($fieldErrors[$field])) {
            $fieldErrors[$field] = $newError;
        }
    }
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

$contactRequestId = substr(sha1(uniqid('', true)), 0, 12);
$contactIp = grinco_client_ip();

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    contact_log('Requête refusée : méthode HTTP différente de POST.');
    grinco_security_log('contact', 'rejected', 'invalid_method', 0, 0, '', $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

contact_log('Requête POST reçue.');

if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 30000) {
    contact_log('Validation refusée : taille de requête supérieure à 30 000 octets.');
    grinco_security_log('contact', 'rejected', 'request_too_large', 0, 0, '', $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

$_SESSION['contact_old'] = contact_capture_old_input();
$rawEmailForLog = trim(grinco_post_value('email'));

if (!grinco_validate_csrf_token('contact', grinco_post_value('csrf_token'))) {
    contact_log('Validation refusée : jeton CSRF absent ou invalide.');
    grinco_security_log('contact', 'rejected', 'invalid_csrf', 100, 0, $rawEmailForLog, $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Votre session a expiré. Veuillez recharger la page et recommencer.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

if (grinco_post_value('website') !== '' || grinco_post_value('company_url') !== '') {
    contact_log('Soumission neutralisée : honeypot rempli.');
    grinco_security_log('contact', 'rejected', 'honeypot', 100, 0, $rawEmailForLog, $contactRequestId);
    unset($_SESSION['contact_old']);
    grinco_set_form_flash('contact', 'success', 'Votre message a bien été envoyé. Notre équipe vous répondra dans les meilleurs délais.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

$timingResult = grinco_validate_form_timing('contact', grinco_post_value('form_started_at'));
if (!$timingResult['valid']) {
    contact_log('Validation refusée : contrôle de durée (' . $timingResult['reason'] . ').');
    grinco_security_log('contact', 'rejected', $timingResult['reason'], 5, 0, $rawEmailForLog, $contactRequestId);
    $timingMessage = $timingResult['reason'] === 'submitted_too_fast'
        ? 'Veuillez attendre quelques secondes avant d’envoyer le formulaire.'
        : 'Votre session a expiré. Veuillez recharger la page et recommencer.';
    grinco_set_form_flash('contact', 'error', $timingMessage, array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

if (!grinco_validate_request_origin()) {
    contact_log('Validation refusée : origine étrangère au site.');
    grinco_security_log('contact', 'rejected', 'foreign_origin', 100, 0, $rawEmailForLog, $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

$rateResult = grinco_check_rate_limit('contact', $contactIp, $rawEmailForLog);
if (!$rateResult['allowed']) {
    contact_log('Validation refusée : ' . $rateResult['reason'] . '.');
    grinco_security_log('contact', 'rejected', $rateResult['reason'], 0, 0, $rawEmailForLog, $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Plusieurs tentatives ont été détectées. Veuillez patienter avant de soumettre une nouvelle demande.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

$turnstileResult = grinco_validate_turnstile(grinco_post_value('cf-turnstile-response'), $contactIp);
if (!$turnstileResult['valid']) {
    contact_log('Validation refusée : Turnstile ' . $turnstileResult['reason'] . '.');
    grinco_security_log('contact', 'rejected', 'turnstile_' . $turnstileResult['reason'], 100, 0, $rawEmailForLog, $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'La vérification de sécurité a échoué. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

$errors = array();
$fieldErrors = array();
$rawName = grinco_post_value('full_name');
$rawEmail = grinco_post_value('email');
$rawPhone = grinco_post_value('phone');
$rawSubject = grinco_post_value('subject');
$rawMessage = grinco_post_value('message');

$nameResult = grinco_validate_name($rawName, 2, 100);
$emailResult = grinco_validate_email_address($rawEmail);
$phoneResult = grinco_validate_phone($rawPhone, false);
contact_add_field_errors($errors, $fieldErrors, 'full_name', $nameResult['errors']);
contact_add_field_errors($errors, $fieldErrors, 'email', $emailResult['errors']);
contact_add_field_errors($errors, $fieldErrors, 'phone', $phoneResult['errors']);

$data = array(
    'full_name' => $nameResult['value'],
    'email' => $emailResult['value'],
    'phone' => $phoneResult['value'],
    'subject' => grinco_normalize_text(strip_tags($rawSubject), false),
    'message' => grinco_normalize_text(strip_tags($rawMessage), true)
);

if (
    grinco_detect_header_injection($rawName)
    || grinco_detect_header_injection($rawEmail)
    || grinco_detect_header_injection($rawSubject)
) {
    $errors[] = 'Certaines informations contiennent des caractères non autorisés.';
    $fieldErrors['subject'] = 'Le sujet contient des caractères non autorisés.';
}
if (
    grinco_has_forbidden_control_characters($rawSubject, false)
    || grinco_utf8_length($data['subject']) < 3
    || grinco_utf8_length($data['subject']) > 150
) {
    $fieldErrors['subject'] = 'Veuillez indiquer un sujet valide de 3 à 150 caractères.';
    $errors[] = $fieldErrors['subject'];
}
if (
    grinco_has_forbidden_control_characters($rawMessage, true)
    || grinco_utf8_length($data['message']) < 10
    || grinco_utf8_length($data['message']) > 3000
) {
    $fieldErrors['message'] = 'Veuillez saisir un message de 10 à 3 000 caractères.';
    $errors[] = $fieldErrors['message'];
}

$_SESSION['contact_old'] = $data;

$spamResult = grinco_analyze_spam(
    'contact',
    array(
        'name' => $rawName,
        'subject' => $rawSubject,
        'message' => $rawMessage
    ),
    $rawSubject
);
$emailSuspicionScore = grinco_email_suspicion_score($data['email']);
if ($emailSuspicionScore > 0) {
    $spamResult['score'] += $emailSuspicionScore;
    if ($spamResult['reason'] === 'accepted') {
        $spamResult['reason'] = 'suspicious_email_domain';
    }
}
if (grinco_request_user_agent_hash() === '') {
    $spamResult['score'] += 2;
    if ($spamResult['reason'] === 'accepted') {
        $spamResult['reason'] = 'empty_user_agent';
    }
    $securityConfig = grinco_form_security_config();
    $spamResult['rejected'] = $spamResult['score'] >= (int) $securityConfig['spam']['reject_score'];
}
$securityConfig = grinco_form_security_config();
$spamResult['rejected'] = $spamResult['score'] >= (int) $securityConfig['spam']['reject_score'];

if ($spamResult['rejected']) {
    contact_log('Validation refusée par l’analyse anti-spam.');
    grinco_security_log('contact', 'rejected', $spamResult['reason'], $spamResult['score'], $spamResult['url_count'], $data['email'], $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

if (!empty($errors)) {
    contact_log('Validation refusée : un ou plusieurs champs sont invalides.');
    grinco_security_log('contact', 'rejected', 'validation_error', $spamResult['score'], $spamResult['url_count'], $data['email'], $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Certaines informations sont invalides. Veuillez vérifier les champs indiqués.', $errors, $fieldErrors);
    grinco_finalize_form_attempt('contact');
    grinco_redirect_form('contact');
}

contact_log('Validation réussie.');

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
    $clientIp = grinco_mask_ip($contactIp);
    $mailSubject = 'Contact GRINCO — Nouveau message de ' . $data['full_name'];
    $whatsappUrl = contact_whatsapp_url($data['phone']);

    $visitorRows = ''
        . grinco_email_row('Nom complet', $data['full_name'])
        . grinco_email_row('E-mail', $data['email'])
        . grinco_email_row('Téléphone', $data['phone'])
        . grinco_email_row('Sujet', $data['subject'])
        . grinco_email_row('Type de formulaire', 'Contact')
        . grinco_email_row('Date et heure', $receivedAt)
        . grinco_email_row('Adresse IP', $clientIp);

    $whatsappButton = '';
    if ($whatsappUrl !== '') {
        $whatsappButton = '<td style="padding-left:10px;">'
            . '<a href="' . grinco_email_escape($whatsappUrl) . '" style="display:inline-block;padding:13px 18px;border:1px solid #3A884C;border-radius:6px;color:#3A884C;font-family:Arial,sans-serif;font-size:13px;font-weight:700;text-decoration:none;">Contacter par WhatsApp</a>'
            . '</td>';
    }

    $emailBody = '<!doctype html><html lang="fr"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Nouveau message GRINCO RDC</title></head>'
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
        . '<div style="padding:18px;border:1px solid #E8ECE9;border-radius:8px;color:#1F2933;font-size:14px;line-height:1.7;">' . nl2br(grinco_email_escape($data['message'])) . '</div></td></tr>'
        . '<tr><td style="padding:0 30px 30px;"><table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td><a href="mailto:' . grinco_email_escape($data['email']) . '" style="display:inline-block;padding:14px 20px;border-radius:6px;background:#3A884C;color:#FFFFFF;font-size:13px;font-weight:700;text-decoration:none;">Répondre par e-mail</a></td>'
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

    grinco_security_log('contact', 'accepted', 'smtp_sent', $spamResult['score'], $spamResult['url_count'], $data['email'], $contactRequestId);
    unset($_SESSION['contact_old']);
    grinco_set_form_flash('contact', 'success', 'Votre message a bien été envoyé. Notre équipe vous répondra dans les meilleurs délais.', array(), array());
} catch (\Exception $exception) {
    contact_log('Échec du traitement ou de l’envoi SMTP : ' . $exception->getMessage());
    grinco_security_log('contact', 'error', 'smtp_error', $spamResult['score'], $spamResult['url_count'], $data['email'], $contactRequestId);
    grinco_set_form_flash('contact', 'error', 'Votre message n’a pas pu être envoyé. Veuillez réessayer ou nous contacter directement à info@grincodrc.com.', array(), array());
}

grinco_finalize_form_attempt('contact');
grinco_redirect_form('contact');
