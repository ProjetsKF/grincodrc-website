<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function quote_redirect()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Location: demande-devis.php');
    exit;
}

function quote_set_flash($type, $message, $errors)
{
    unset($_SESSION['quote_success'], $_SESSION['quote_error'], $_SESSION['quote_validation_errors']);

    if ($type === 'success') {
        $_SESSION['quote_success'] = $message;
    } else {
        $_SESSION['quote_error'] = $message;
    }

    if (is_array($errors) && !empty($errors)) {
        $_SESSION['quote_validation_errors'] = array_values($errors);
    }
}

function quote_log($message)
{
    global $quoteRequestId;
    error_log('[GRINCO quote form][' . $quoteRequestId . '] ' . $message);
}

function quote_post_value($name)
{
    if (!isset($_POST[$name]) || !is_scalar($_POST[$name])) {
        return '';
    }

    return (string) $_POST[$name];
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
        $value = quote_post_value($fieldName);
        $oldInput[$fieldName] = $fieldName === 'consent'
            ? ($value === '1' ? '1' : '')
            : trim(strip_tags($value));
    }

    return $oldInput;
}

function quote_clean_field($name, $label, $maxLength, $multiline, &$errors)
{
    $rawValue = quote_post_value($name);
    $value = strip_tags($rawValue);
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

function quote_has_header_injection($value)
{
    return preg_match('/[\r\n]/', $value) === 1;
}

function quote_email_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function quote_email_value($value)
{
    if ($value === '') {
        return '<span style="color:#8A949E;">Non renseigné</span>';
    }

    return nl2br(quote_email_escape($value));
}

function quote_email_row($label, $value)
{
    return '<tr>'
        . '<td width="34%" valign="top" style="padding:11px 14px;border-bottom:1px solid #E8ECE9;color:#5F6B76;font-size:13px;font-weight:600;">' . quote_email_escape($label) . '</td>'
        . '<td valign="top" style="padding:11px 14px;border-bottom:1px solid #E8ECE9;color:#1F2933;font-size:14px;line-height:1.55;">' . quote_email_value($value) . '</td>'
        . '</tr>';
}

$quoteRequestId = substr(sha1(uniqid('', true)), 0, 12);

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    quote_log('Requête refusée : méthode HTTP différente de POST.');
    quote_set_flash('error', 'Cette action nécessite l’envoi du formulaire de demande de devis.', array());
    quote_redirect();
}

quote_log('Requête POST reçue.');

if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 50000) {
    quote_log('Validation refusée : taille de requête supérieure à 50 000 octets.');
    quote_set_flash('error', 'La demande contient trop de données. Veuillez réduire la longueur de votre message.', array());
    quote_redirect();
}

$_SESSION['quote_old'] = quote_capture_old_input();

$csrfToken = quote_post_value('csrf_token');
$sessionToken = isset($_SESSION['quote_csrf_token']) ? (string) $_SESSION['quote_csrf_token'] : '';

if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    quote_log('Validation refusée : jeton CSRF absent ou invalide.');
    quote_set_flash('error', 'Votre session a expiré. Veuillez recharger la page et recommencer.', array());
    quote_redirect();
}

if (quote_post_value('website') !== '') {
    quote_log('Soumission neutralisée : honeypot rempli.');
    unset($_SESSION['quote_old']);
    $_SESSION['quote_csrf_token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(32))
        : bin2hex(openssl_random_pseudo_bytes(32));
    quote_set_flash('success', 'Votre demande a été envoyée avec succès. Notre équipe vous contactera après analyse de votre besoin.', array());
    quote_redirect();
}

$formStartedAt = isset($_SESSION['quote_form_started_at']) ? (int) $_SESSION['quote_form_started_at'] : 0;
if ($formStartedAt <= 0 || (time() - $formStartedAt) < 3) {
    quote_log('Validation refusée : délai minimal de trois secondes non respecté.');
    quote_set_flash('error', 'Veuillez attendre quelques secondes avant d’envoyer le formulaire.', array());
    quote_redirect();
}

$now = time();
$attempts = isset($_SESSION['quote_submission_attempts']) && is_array($_SESSION['quote_submission_attempts'])
    ? $_SESSION['quote_submission_attempts']
    : array();
$recentAttempts = array();

foreach ($attempts as $attemptTimestamp) {
    if (is_numeric($attemptTimestamp) && (int) $attemptTimestamp > ($now - 900)) {
        $recentAttempts[] = (int) $attemptTimestamp;
    }
}

if (count($recentAttempts) >= 3) {
    quote_log('Validation refusée : limitation de trois tentatives sur quinze minutes atteinte.');
    quote_set_flash('error', 'Vous avez effectué trop de tentatives. Veuillez réessayer dans quinze minutes.', array());
    quote_redirect();
}

$errors = array();

$rawName = quote_post_value('full_name');
$rawEmail = quote_post_value('email');
if (quote_has_header_injection($rawName) || quote_has_header_injection($rawEmail)) {
    $errors[] = 'Les coordonnées fournies contiennent des caractères non autorisés.';
}

$data = array(
    'full_name' => quote_clean_field('full_name', 'Le nom complet', 120, false, $errors),
    'company' => quote_clean_field('company', 'Le nom de l’entreprise', 150, false, $errors),
    'email' => trim($rawEmail),
    'phone' => quote_clean_field('phone', 'Le numéro de téléphone', 40, false, $errors),
    'whatsapp' => quote_clean_field('whatsapp', 'Le numéro WhatsApp', 40, false, $errors),
    'city' => quote_clean_field('city', 'La ville', 100, false, $errors),
    'province' => quote_clean_field('province', 'La province', 100, false, $errors),
    'category' => quote_clean_field('category', 'La catégorie', 80, false, $errors),
    'brand' => quote_clean_field('brand', 'La marque', 100, false, $errors),
    'model' => quote_clean_field('model', 'Le modèle', 100, false, $errors),
    'quantity' => quote_clean_field('quantity', 'La quantité', 6, false, $errors),
    'intended_use' => quote_clean_field('intended_use', 'L’utilisation prévue', 1000, true, $errors),
    'technical_requirements' => quote_clean_field('technical_requirements', 'Les caractéristiques techniques', 3000, true, $errors),
    'delivery_location' => quote_clean_field('delivery_location', 'Le lieu de livraison', 200, false, $errors),
    'desired_deadline' => quote_clean_field('desired_deadline', 'Le délai souhaité', 100, false, $errors),
    'indicative_budget' => quote_clean_field('indicative_budget', 'Le budget indicatif', 100, false, $errors),
    'additional_message' => quote_clean_field('additional_message', 'Le message complémentaire', 3000, true, $errors),
    'consent' => quote_post_value('consent') === '1' ? '1' : ''
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
    }
}

if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'L’adresse e-mail n’est pas valide.';
}

$phonePattern = '/^[0-9+().\/\s-]+$/';
if ($data['phone'] !== '' && (!preg_match($phonePattern, $data['phone']) || strlen(preg_replace('/\D+/', '', $data['phone'])) < 6)) {
    $errors[] = 'Le numéro de téléphone n’est pas valide.';
}
if ($data['whatsapp'] !== '' && (!preg_match($phonePattern, $data['whatsapp']) || strlen(preg_replace('/\D+/', '', $data['whatsapp'])) < 6)) {
    $errors[] = 'Le numéro WhatsApp n’est pas valide.';
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
    $errors[] = 'La catégorie sélectionnée n’est pas valide.';
}

if ($data['quantity'] !== '' && (!ctype_digit($data['quantity']) || (int) $data['quantity'] < 1 || (int) $data['quantity'] > 999999)) {
    $errors[] = 'La quantité doit être un nombre entier supérieur à zéro.';
}

if ($data['consent'] !== '1') {
    $errors[] = 'Vous devez accepter l’utilisation de vos informations pour le traitement de la demande.';
}

$_SESSION['quote_old'] = $data;

if (!empty($errors)) {
    quote_log('Validation refusée : un ou plusieurs champs sont invalides.');
    quote_set_flash('error', 'Veuillez compléter tous les champs obligatoires et corriger les informations signalées.', array_values(array_unique($errors)));
    quote_redirect();
}

quote_log('Validation réussie.');

$recentAttempts[] = $now;
$_SESSION['quote_submission_attempts'] = $recentAttempts;

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
    $subjectName = preg_replace('/[\r\n]+/', ' ', $data['full_name']);
    $subjectCategory = preg_replace('/[\r\n]+/', ' ', $data['category']);
    $subject = 'Nouvelle demande de devis GRINCO – ' . $subjectName . ' – ' . $subjectCategory;

    $whatsappDigits = preg_replace('/\D+/', '', $data['whatsapp']);
    $whatsappUrl = strlen($whatsappDigits) >= 8 && strlen($whatsappDigits) <= 15
        ? 'https://wa.me/' . $whatsappDigits
        : '';

    $logoHtml = '';
    if (!empty($mailConfig['logo_url']) && filter_var($mailConfig['logo_url'], FILTER_VALIDATE_URL)) {
        $logoHtml = '<img src="' . quote_email_escape($mailConfig['logo_url']) . '" width="150" alt="GRINCO RDC" style="display:block;max-width:150px;height:auto;border:0;">';
    } else {
        $logoHtml = '<div style="color:#3A884C;font-family:Arial,sans-serif;font-size:25px;font-weight:700;letter-spacing:-0.5px;">GRINCO RDC</div>';
    }

    $clientRows = ''
        . quote_email_row('Nom complet', $data['full_name'])
        . quote_email_row('Entreprise', $data['company'])
        . quote_email_row('E-mail', $data['email'])
        . quote_email_row('Téléphone', $data['phone'])
        . quote_email_row('WhatsApp', $data['whatsapp'])
        . quote_email_row('Ville', $data['city'])
        . quote_email_row('Province', $data['province']);

    $requestRows = ''
        . quote_email_row('Catégorie', $data['category'])
        . quote_email_row('Marque', $data['brand'])
        . quote_email_row('Modèle', $data['model'])
        . quote_email_row('Quantité', $data['quantity'])
        . quote_email_row('Usage prévu', $data['intended_use'])
        . quote_email_row('Caractéristiques techniques', $data['technical_requirements'])
        . quote_email_row('Lieu de livraison', $data['delivery_location'])
        . quote_email_row('Délai souhaité', $data['desired_deadline'])
        . quote_email_row('Budget indicatif', $data['indicative_budget'])
        . quote_email_row('Message complémentaire', $data['additional_message']);

    $whatsappButton = '';
    if ($whatsappUrl !== '') {
        $whatsappButton = '<td style="padding:0 0 0 10px;">'
            . '<a href="' . quote_email_escape($whatsappUrl) . '" style="display:inline-block;padding:13px 18px;border:1px solid #3A884C;border-radius:6px;color:#3A884C;font-family:Arial,sans-serif;font-size:13px;font-weight:700;text-decoration:none;">Contacter sur WhatsApp</a>'
            . '</td>';
    }

    $emailBody = '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Nouvelle demande de devis</title></head>'
        . '<body style="margin:0;padding:0;background:#F4F6F5;font-family:Arial,Helvetica,sans-serif;color:#1F2933;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#F4F6F5;padding:30px 10px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:700px;background:#FFFFFF;border-top:6px solid #3A884C;border-bottom:6px solid #CF2E2E;border-radius:12px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,0.08);">'
        . '<tr><td style="padding:28px 30px 22px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td valign="middle">' . $logoHtml . '<div style="margin-top:8px;color:#5F6B76;font-size:13px;">Nouvelle demande de devis</div></td>'
        . '<td align="right" valign="middle"><span style="display:inline-block;padding:8px 13px;border-radius:20px;background:#FCEAEA;color:#CF2E2E;font-size:12px;font-weight:700;text-transform:uppercase;">À traiter</span></td>'
        . '</tr></table></td></tr>'
        . '<tr><td style="padding:0 30px 24px;">'
        . '<div style="padding:16px 18px;border-radius:8px;background:#EAF4EC;color:#1F2933;font-size:14px;"><strong>Demande reçue le ' . quote_email_escape($receivedAt) . '</strong></div>'
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
        . '<td><a href="mailto:' . quote_email_escape($data['email']) . '" style="display:inline-block;padding:14px 20px;border-radius:6px;background:#3A884C;color:#FFFFFF;font-size:13px;font-weight:700;text-decoration:none;">Répondre au client</a></td>'
        . $whatsappButton
        . '</tr></table></td></tr>'
        . '<tr><td style="padding:22px 30px;background:#F7F9F8;border-top:1px solid #E8ECE9;color:#5F6B76;font-size:12px;line-height:1.6;text-align:center;">'
        . 'Ce message a été envoyé depuis le formulaire de demande de devis du site web GRINCO RDC.<br>'
        . '<a href="mailto:info@grincodrc.com" style="color:#3A884C;font-weight:700;text-decoration:none;">info@grincodrc.com</a>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';

    $altBody = "GRINCO RDC - Nouvelle demande de devis\n"
        . "Demande reçue le " . $receivedAt . "\n\n"
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

    unset($_SESSION['quote_old'], $_SESSION['quote_form_started_at']);
    $_SESSION['quote_csrf_token'] = function_exists('random_bytes')
        ? bin2hex(random_bytes(32))
        : bin2hex(openssl_random_pseudo_bytes(32));
    quote_set_flash('success', 'Votre demande a été envoyée avec succès. Notre équipe vous contactera après analyse de votre besoin.', array());
} catch (Exception $exception) {
    quote_log('Échec du traitement ou de l’envoi SMTP : ' . $exception->getMessage());
    quote_set_flash('error', 'Votre demande n’a pas pu être envoyée. Veuillez réessayer ou contacter GRINCO RDC directement à info@grincodrc.com.', array());
}

quote_redirect();
