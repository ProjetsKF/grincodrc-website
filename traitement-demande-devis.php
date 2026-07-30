<?php
require_once __DIR__ . '/includes/form-security.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

function quote_log($message)
{
    global $quoteRequestId;
    error_log('[GRINCO quote form][' . $quoteRequestId . '] ' . $message);
}

function quote_capture_old_input()
{
    $fieldNames = array(
        'full_name',
        'company',
        'email',
        'phone',
        'whatsapp',
        'city',
        'province',
        'category',
        'brand',
        'model',
        'quantity',
        'intended_use',
        'technical_requirements',
        'delivery_location',
        'desired_deadline',
        'indicative_budget',
        'additional_message',
        'consent'
    );
    $oldInput = array();

    foreach ($fieldNames as $fieldName) {
        $value = grinco_post_value($fieldName);
        $multiline = in_array($fieldName, array('intended_use', 'technical_requirements', 'additional_message'), true);
        $oldInput[$fieldName] = $fieldName === 'consent'
            ? ($value === '1' ? '1' : '')
            : grinco_normalize_text(strip_tags($value), $multiline);
    }

    return $oldInput;
}

function quote_clean_field($name, $label, $maxLength, $multiline, &$errors, &$fieldErrors)
{
    $rawValue = grinco_post_value($name);
    $value = grinco_normalize_text(strip_tags($rawValue), $multiline);

    if (grinco_utf8_length($value) > $maxLength) {
        $message = $label . ' dépasse la longueur autorisée.';
        $errors[] = $message;
        $fieldErrors[$name] = $message;
        return grinco_utf8_substr($value, 0, $maxLength);
    }
    if (grinco_has_forbidden_control_characters($rawValue, $multiline)) {
        $message = $label . ' contient des caractères non autorisés.';
        $errors[] = $message;
        $fieldErrors[$name] = $message;
    }

    return $value;
}

function quote_add_field_errors(&$errors, &$fieldErrors, $field, $newErrors)
{
    foreach ($newErrors as $newError) {
        $errors[] = $newError;
        if (empty($fieldErrors[$field])) {
            $fieldErrors[$field] = $newError;
        }
    }
}

$quoteRequestId = substr(sha1(uniqid('', true)), 0, 12);
$quoteIp = grinco_client_ip();

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    quote_log('Requête refusée : méthode HTTP différente de POST.');
    grinco_security_log('quote', 'rejected', 'invalid_method', 0, 0, '', $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

quote_log('Requête POST reçue.');

if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 50000) {
    quote_log('Validation refusée : taille de requête supérieure à 50 000 octets.');
    grinco_security_log('quote', 'rejected', 'request_too_large', 0, 0, '', $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

$_SESSION['quote_old'] = quote_capture_old_input();
$rawEmailForLog = trim(grinco_post_value('email'));

if (!grinco_validate_csrf_token('quote', grinco_post_value('csrf_token'))) {
    quote_log('Validation refusée : jeton CSRF absent ou invalide.');
    grinco_security_log('quote', 'rejected', 'invalid_csrf', 100, 0, $rawEmailForLog, $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Votre session a expiré. Veuillez recharger la page et recommencer.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

if (grinco_post_value('website') !== '' || grinco_post_value('company_url') !== '') {
    quote_log('Soumission neutralisée : honeypot rempli.');
    grinco_security_log('quote', 'rejected', 'honeypot', 100, 0, $rawEmailForLog, $quoteRequestId);
    unset($_SESSION['quote_old']);
    grinco_set_form_flash('quote', 'success', 'Votre demande de devis a bien été transmise. L’équipe GRINCO vous contactera après analyse de votre besoin.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

$timingResult = grinco_validate_form_timing('quote', grinco_post_value('form_started_at'));
if (!$timingResult['valid']) {
    quote_log('Validation refusée : contrôle de durée (' . $timingResult['reason'] . ').');
    grinco_security_log('quote', 'rejected', $timingResult['reason'], 5, 0, $rawEmailForLog, $quoteRequestId);
    $timingMessage = $timingResult['reason'] === 'submitted_too_fast'
        ? 'Veuillez attendre quelques secondes avant d’envoyer le formulaire.'
        : 'Votre session a expiré. Veuillez recharger la page et recommencer.';
    grinco_set_form_flash('quote', 'error', $timingMessage, array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

if (!grinco_validate_request_origin()) {
    quote_log('Validation refusée : origine étrangère au site.');
    grinco_security_log('quote', 'rejected', 'foreign_origin', 100, 0, $rawEmailForLog, $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

$rateResult = grinco_check_rate_limit('quote', $quoteIp, $rawEmailForLog);
if (!$rateResult['allowed']) {
    quote_log('Validation refusée : ' . $rateResult['reason'] . '.');
    grinco_security_log('quote', 'rejected', $rateResult['reason'], 0, 0, $rawEmailForLog, $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Plusieurs tentatives ont été détectées. Veuillez patienter avant de soumettre une nouvelle demande.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

$turnstileResult = grinco_validate_turnstile(grinco_post_value('cf-turnstile-response'), $quoteIp);
if (!$turnstileResult['valid']) {
    quote_log('Validation refusée : Turnstile ' . $turnstileResult['reason'] . '.');
    grinco_security_log('quote', 'rejected', 'turnstile_' . $turnstileResult['reason'], 100, 0, $rawEmailForLog, $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'La vérification de sécurité a échoué. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

$errors = array();
$fieldErrors = array();

$rawName = grinco_post_value('full_name');
$rawEmail = grinco_post_value('email');
$rawPhone = grinco_post_value('phone');
$rawWhatsapp = grinco_post_value('whatsapp');
if (grinco_detect_header_injection($rawName) || grinco_detect_header_injection($rawEmail)) {
    $errors[] = 'Les coordonnées fournies contiennent des caractères non autorisés.';
    $fieldErrors['full_name'] = 'Le nom contient des caractères non autorisés.';
}

$nameResult = grinco_validate_name($rawName, 2, 100);
$emailResult = grinco_validate_email_address($rawEmail);
$phoneResult = grinco_validate_phone($rawPhone, true);
$whatsappResult = grinco_validate_phone($rawWhatsapp, false);
quote_add_field_errors($errors, $fieldErrors, 'full_name', $nameResult['errors']);
quote_add_field_errors($errors, $fieldErrors, 'email', $emailResult['errors']);
quote_add_field_errors($errors, $fieldErrors, 'phone', $phoneResult['errors']);
quote_add_field_errors($errors, $fieldErrors, 'whatsapp', $whatsappResult['errors']);

$data = array(
    'full_name' => $nameResult['value'],
    'company' => quote_clean_field('company', 'Le nom de l’entreprise', 150, false, $errors, $fieldErrors),
    'email' => $emailResult['value'],
    'phone' => $phoneResult['value'],
    'whatsapp' => $whatsappResult['value'],
    'city' => quote_clean_field('city', 'La ville', 100, false, $errors, $fieldErrors),
    'province' => quote_clean_field('province', 'La province', 100, false, $errors, $fieldErrors),
    'category' => quote_clean_field('category', 'La catégorie', 80, false, $errors, $fieldErrors),
    'brand' => quote_clean_field('brand', 'La marque', 100, false, $errors, $fieldErrors),
    'model' => quote_clean_field('model', 'Le modèle', 100, false, $errors, $fieldErrors),
    'quantity' => quote_clean_field('quantity', 'La quantité', 4, false, $errors, $fieldErrors),
    'intended_use' => quote_clean_field('intended_use', 'L’utilisation prévue', 1000, true, $errors, $fieldErrors),
    'technical_requirements' => quote_clean_field('technical_requirements', 'Les caractéristiques techniques', 3000, true, $errors, $fieldErrors),
    'delivery_location' => quote_clean_field('delivery_location', 'Le lieu de livraison', 200, false, $errors, $fieldErrors),
    'desired_deadline' => quote_clean_field('desired_deadline', 'Le délai souhaité', 100, false, $errors, $fieldErrors),
    'indicative_budget' => quote_clean_field('indicative_budget', 'Le budget indicatif', 100, false, $errors, $fieldErrors),
    'additional_message' => quote_clean_field('additional_message', 'Le message complémentaire', 3000, true, $errors, $fieldErrors),
    'consent' => grinco_post_value('consent') === '1' ? '1' : ''
);

$requiredFields = array(
    'full_name' => 'Le nom complet est obligatoire.',
    'email' => 'L’adresse e-mail est obligatoire.',
    'phone' => 'Le numéro de téléphone est obligatoire.',
    'category' => 'La catégorie d’équipement est obligatoire.',
    'quantity' => 'La quantité est obligatoire.',
    'delivery_location' => 'Le lieu de livraison est obligatoire.'
);

foreach ($requiredFields as $field => $message) {
    if ($data[$field] === '') {
        $errors[] = $message;
        $fieldErrors[$field] = $message;
    }
}

$allowedCategories = array(
    'Camion',
    'Semi-remorque',
    'Engin lourd',
    'Véhicule particulier',
    'Pièces de rechange',
    'Service d’ingénierie',
    'Maintenance industrielle',
    'Autre'
);

if ($data['category'] !== '' && !in_array($data['category'], $allowedCategories, true)) {
    $fieldErrors['category'] = 'La catégorie sélectionnée n’est pas valide.';
    $errors[] = $fieldErrors['category'];
}

if ($data['quantity'] !== '' && (!ctype_digit($data['quantity']) || (int) $data['quantity'] < 1 || (int) $data['quantity'] > 1000)) {
    $fieldErrors['quantity'] = 'La quantité doit être un entier compris entre 1 et 1 000.';
    $errors[] = $fieldErrors['quantity'];
}

if (
    $data['indicative_budget'] !== ''
    && (
        strpos($data['indicative_budget'], '-') !== false
        || !preg_match('/^(?=.*\d)[0-9\s.,\'’A-Za-z$€CDFUSDFCFA]+$/u', $data['indicative_budget'])
    )
) {
    $fieldErrors['indicative_budget'] = 'Le budget doit contenir un montant positif et, si nécessaire, une devise.';
    $errors[] = $fieldErrors['indicative_budget'];
}

if ($data['consent'] !== '1') {
    $fieldErrors['consent'] = 'Vous devez accepter l’utilisation de vos informations pour le traitement de la demande.';
    $errors[] = $fieldErrors['consent'];
}

$_SESSION['quote_old'] = $data;

$spamFields = array(
    'name' => $rawName,
    'company' => grinco_post_value('company'),
    'city' => grinco_post_value('city'),
    'province' => grinco_post_value('province'),
    'brand' => grinco_post_value('brand'),
    'model' => grinco_post_value('model'),
    'intended_use' => grinco_post_value('intended_use'),
    'technical_requirements' => grinco_post_value('technical_requirements'),
    'delivery_location' => grinco_post_value('delivery_location'),
    'desired_deadline' => grinco_post_value('desired_deadline'),
    'indicative_budget' => grinco_post_value('indicative_budget'),
    'additional_message' => grinco_post_value('additional_message')
);
$spamResult = grinco_analyze_spam('quote', $spamFields, '');
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
    quote_log('Validation refusée par l’analyse anti-spam.');
    grinco_security_log('quote', 'rejected', $spamResult['reason'], $spamResult['score'], $spamResult['url_count'], $data['email'], $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Votre demande n’a pas pu être traitée. Veuillez réessayer.', array(), array());
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

if (!empty($errors)) {
    quote_log('Validation refusée : un ou plusieurs champs sont invalides.');
    grinco_security_log('quote', 'rejected', 'validation_error', $spamResult['score'], $spamResult['url_count'], $data['email'], $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Certaines informations sont invalides. Veuillez vérifier les champs indiqués.', $errors, $fieldErrors);
    grinco_finalize_form_attempt('quote');
    grinco_redirect_form('quote');
}

quote_log('Validation réussie.');

try {
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    $phpMailerSourcePath = __DIR__ . '/vendor/phpmailer/phpmailer/src';
    $phpMailerManualFiles = array(
        $phpMailerSourcePath . '/Exception.php',
        $phpMailerSourcePath . '/PHPMailer.php',
        $phpMailerSourcePath . '/SMTP.php'
    );
    $configPath = __DIR__ . '/config/mail.php';

    if (!is_file($configPath)) {
        throw new RuntimeException('Le fichier de configuration SMTP est introuvable.');
    }

    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    } elseif (
        is_file($phpMailerManualFiles[0])
        && is_file($phpMailerManualFiles[1])
        && is_file($phpMailerManualFiles[2])
    ) {
        require_once $phpMailerManualFiles[0];
        require_once $phpMailerManualFiles[1];
        require_once $phpMailerManualFiles[2];
    } else {
        throw new RuntimeException('Les fichiers requis de PHPMailer sont introuvables.');
    }

    $mailConfig = require $configPath;

    if (!is_array($mailConfig)) {
        throw new RuntimeException('La configuration SMTP est invalide.');
    }

    $requiredConfig = array('host', 'port', 'username', 'password', 'encryption', 'from_email', 'from_name', 'recipient_email');
    foreach ($requiredConfig as $configKey) {
        if (!isset($mailConfig[$configKey]) || trim((string) $mailConfig[$configKey]) === '' || (string) $mailConfig[$configKey] === 'À_COMPLETER') {
            throw new RuntimeException('Un paramètre SMTP obligatoire reste à configurer : ' . $configKey);
        }
    }

    if (!class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        throw new RuntimeException('La classe PHPMailer est indisponible.');
    }

    quote_log('PHPMailer chargé correctement.');

    date_default_timezone_set('Africa/Lubumbashi');
    $receivedAt = date('d/m/Y à H:i');
    $subjectIdentity = $data['company'] !== '' ? $data['company'] : $data['full_name'];
    $subject = 'Demande de devis GRINCO — ' . $data['category'] . ' — ' . $subjectIdentity;
    $maskedIp = grinco_mask_ip($quoteIp);

    $whatsappDigits = preg_replace('/\D+/', '', $data['whatsapp']);
    $whatsappUrl = strlen($whatsappDigits) >= 8 && strlen($whatsappDigits) <= 15
        ? 'https://wa.me/' . $whatsappDigits
        : '';

    $logoHtml = '';
    if (!empty($mailConfig['logo_url']) && filter_var($mailConfig['logo_url'], FILTER_VALIDATE_URL)) {
        $logoHtml = '<img src="' . grinco_email_escape($mailConfig['logo_url']) . '" width="150" alt="GRINCO RDC" style="display:block;max-width:150px;height:auto;border:0;">';
    } else {
        $logoHtml = '<div style="color:#3A884C;font-family:Arial,sans-serif;font-size:25px;font-weight:700;letter-spacing:-0.5px;">GRINCO RDC</div>';
    }

    $clientRows = ''
        . grinco_email_row('Nom complet', $data['full_name'])
        . grinco_email_row('Entreprise', $data['company'])
        . grinco_email_row('E-mail', $data['email'])
        . grinco_email_row('Téléphone', $data['phone'])
        . grinco_email_row('WhatsApp', $data['whatsapp'])
        . grinco_email_row('Ville', $data['city'])
        . grinco_email_row('Province', $data['province'])
        . grinco_email_row('Type de formulaire', 'Demande de devis')
        . grinco_email_row('Adresse IP', $maskedIp);

    $requestRows = ''
        . grinco_email_row('Catégorie', $data['category'])
        . grinco_email_row('Marque', $data['brand'])
        . grinco_email_row('Modèle', $data['model'])
        . grinco_email_row('Quantité', $data['quantity'])
        . grinco_email_row('Usage prévu', $data['intended_use'])
        . grinco_email_row('Caractéristiques techniques', $data['technical_requirements'])
        . grinco_email_row('Lieu de livraison', $data['delivery_location'])
        . grinco_email_row('Délai souhaité', $data['desired_deadline'])
        . grinco_email_row('Budget indicatif', $data['indicative_budget'])
        . grinco_email_row('Message complémentaire', $data['additional_message']);

    $whatsappButton = '';
    if ($whatsappUrl !== '') {
        $whatsappButton = '<td style="padding:0 0 0 10px;">'
            . '<a href="' . grinco_email_escape($whatsappUrl) . '" style="display:inline-block;padding:13px 18px;border:1px solid #3A884C;border-radius:6px;color:#3A884C;font-family:Arial,sans-serif;font-size:13px;font-weight:700;text-decoration:none;">Contacter sur WhatsApp</a>'
            . '</td>';
    }

    $emailBody = '<!doctype html><html lang="fr"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Nouvelle demande de devis</title></head>'
        . '<body style="margin:0;padding:0;background:#F4F6F5;font-family:Arial,Helvetica,sans-serif;color:#1F2933;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#F4F6F5;padding:30px 10px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:700px;background:#FFFFFF;border-top:6px solid #3A884C;border-bottom:6px solid #CF2E2E;border-radius:12px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,0.08);">'
        . '<tr><td style="padding:28px 30px 22px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td valign="middle">' . $logoHtml . '<div style="margin-top:8px;color:#5F6B76;font-size:13px;">Nouvelle demande de devis</div></td>'
        . '<td align="right" valign="middle"><span style="display:inline-block;padding:8px 13px;border-radius:20px;background:#FCEAEA;color:#CF2E2E;font-size:12px;font-weight:700;text-transform:uppercase;">À traiter</span></td>'
        . '</tr></table></td></tr>'
        . '<tr><td style="padding:0 30px 24px;">'
        . '<div style="padding:16px 18px;border-radius:8px;background:#EAF4EC;color:#1F2933;font-size:14px;"><strong>Demande reçue le ' . grinco_email_escape($receivedAt) . '</strong></div>'
        . '</td></tr>'
        . '<tr><td style="padding:0 30px 26px;">'
        . '<div style="margin-bottom:10px;color:#3A884C;font-size:12px;font-weight:700;letter-spacing:1px;">INFORMATIONS DU CLIENT</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #E8ECE9;border-radius:8px;border-collapse:separate;overflow:hidden;">' . $clientRows . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:0 30px 26px;">'
        . '<div style="margin-bottom:10px;color:#3A884C;font-size:12px;font-weight:700;letter-spacing:1px;">INFORMATIONS SUR LA DEMANDE</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #E8ECE9;border-radius:8px;border-collapse:separate;overflow:hidden;">' . $requestRows . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:0 30px 30px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td><a href="mailto:' . grinco_email_escape($data['email']) . '" style="display:inline-block;padding:14px 20px;border-radius:6px;background:#3A884C;color:#FFFFFF;font-size:13px;font-weight:700;text-decoration:none;">Répondre au client</a></td>'
        . $whatsappButton
        . '</tr></table></td></tr>'
        . '<tr><td style="padding:22px 30px;background:#F7F9F8;border-top:1px solid #E8ECE9;color:#5F6B76;font-size:12px;line-height:1.6;text-align:center;">'
        . 'Ce message a été envoyé depuis le formulaire de demande de devis du site web GRINCO RDC.<br>'
        . '<a href="mailto:info@grincodrc.com" style="color:#3A884C;font-weight:700;text-decoration:none;">info@grincodrc.com</a>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';

    $altBody = "GRINCO RDC - Nouvelle demande de devis\n"
        . "Demande reçue le " . $receivedAt . "\n\n"
        . "Type de formulaire : Demande de devis\n"
        . "Adresse IP : " . $maskedIp . "\n\n"
        . "INFORMATIONS DU CLIENT\n"
        . "Nom complet : " . $data['full_name'] . "\n"
        . "Entreprise : " . $data['company'] . "\n"
        . "E-mail : " . $data['email'] . "\n"
        . "Téléphone : " . $data['phone'] . "\n"
        . "WhatsApp : " . $data['whatsapp'] . "\n"
        . "Ville : " . $data['city'] . "\n"
        . "Province : " . $data['province'] . "\n\n"
        . "INFORMATIONS SUR LA DEMANDE\n"
        . "Catégorie : " . $data['category'] . "\n"
        . "Marque : " . $data['brand'] . "\n"
        . "Modèle : " . $data['model'] . "\n"
        . "Quantité : " . $data['quantity'] . "\n"
        . "Usage prévu : " . $data['intended_use'] . "\n"
        . "Caractéristiques techniques : " . $data['technical_requirements'] . "\n"
        . "Lieu de livraison : " . $data['delivery_location'] . "\n"
        . "Délai souhaité : " . $data['desired_deadline'] . "\n"
        . "Budget indicatif : " . $data['indicative_budget'] . "\n"
        . "Message complémentaire : " . $data['additional_message'] . "\n";

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
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
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls' || $encryption === 'starttls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom((string) $mailConfig['from_email'], (string) $mailConfig['from_name']);
    $mail->addAddress((string) $mailConfig['recipient_email']);
    $mail->addReplyTo($data['email'], $data['full_name']);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $emailBody;
    $mail->AltBody = $altBody;
    quote_log('Tentative de connexion et d’envoi SMTP.');
    $mail->send();
    quote_log('Envoi SMTP réussi.');

    grinco_security_log('quote', 'accepted', 'smtp_sent', $spamResult['score'], $spamResult['url_count'], $data['email'], $quoteRequestId);
    unset($_SESSION['quote_old']);
    grinco_set_form_flash('quote', 'success', 'Votre demande de devis a bien été transmise. L’équipe GRINCO vous contactera après analyse de votre besoin.', array(), array());
} catch (Exception $exception) {
    quote_log('Échec du traitement ou de l’envoi SMTP : ' . $exception->getMessage());
    grinco_security_log('quote', 'error', 'smtp_error', $spamResult['score'], $spamResult['url_count'], $data['email'], $quoteRequestId);
    grinco_set_form_flash('quote', 'error', 'Votre demande n’a pas pu être envoyée. Veuillez réessayer ou contacter GRINCO RDC directement à info@grincodrc.com.', array(), array());
}

grinco_finalize_form_attempt('quote');
grinco_redirect_form('quote');
