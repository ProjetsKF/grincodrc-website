<?php
require_once dirname(__DIR__) . '/config/app.php';


function grinco_form_security_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $configPath = dirname(__DIR__) . '/config/form-security.php';
    $localConfigPath = dirname(__DIR__) . '/config/form-security.local.php';
    $config = is_file($configPath) ? require $configPath : array();

    if (is_file($localConfigPath)) {
        $localConfig = require $localConfigPath;
        if (is_array($localConfig)) {
            $config = array_replace_recursive($config, $localConfig);
        }
    }

    if (
        !empty($config['turnstile']['site_key'])
        && !empty($config['turnstile']['secret_key'])
        && !isset($config['turnstile']['enabled'])
    ) {
        $config['turnstile']['enabled'] = true;
    }

    return $config;
}

function grinco_start_secure_session()
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    $isSecure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params(0, '/', '', $isSecure, true);
    session_start();
}

function grinco_apply_form_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function grinco_utf8_length($value)
{
    $value = (string) $value;
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $matches = array();
    if (preg_match_all('/./us', $value, $matches) !== false) {
        return count($matches[0]);
    }

    return strlen($value);
}

function grinco_utf8_substr($value, $start, $length)
{
    $value = (string) $value;
    if (function_exists('mb_substr')) {
        return mb_substr($value, $start, $length, 'UTF-8');
    }

    $matches = array();
    if (preg_match_all('/./us', $value, $matches) !== false) {
        return implode('', array_slice($matches[0], $start, $length));
    }

    return substr($value, $start, $length);
}

function grinco_json_encode($value)
{
    $options = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
    return json_encode($value, $options);
}

function grinco_random_token()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    }

    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    return hash('sha256', uniqid(mt_rand(), true));
}

function grinco_csrf_token($formType)
{
    $key = 'grinco_csrf_' . $formType;
    if (empty($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        $_SESSION[$key] = grinco_random_token();
    }

    return $_SESSION[$key];
}

function grinco_regenerate_csrf_token($formType)
{
    $_SESSION['grinco_csrf_' . $formType] = grinco_random_token();
    return $_SESSION['grinco_csrf_' . $formType];
}

function grinco_hash_equals($knownValue, $receivedValue)
{
    if (function_exists('hash_equals')) {
        return hash_equals($knownValue, $receivedValue);
    }

    if (strlen($knownValue) !== strlen($receivedValue)) {
        return false;
    }

    $difference = 0;
    $length = strlen($knownValue);
    for ($index = 0; $index < $length; $index++) {
        $difference |= ord($knownValue[$index]) ^ ord($receivedValue[$index]);
    }

    return $difference === 0;
}

function grinco_validate_csrf_token($formType, $receivedToken)
{
    $key = 'grinco_csrf_' . $formType;
    $knownToken = isset($_SESSION[$key]) && is_string($_SESSION[$key]) ? $_SESSION[$key] : '';

    return $knownToken !== ''
        && is_string($receivedToken)
        && $receivedToken !== ''
        && grinco_hash_equals($knownToken, $receivedToken);
}

function grinco_mark_form_opened($formType)
{
    $timestamp = time();
    $_SESSION['grinco_form_opened_' . $formType] = $timestamp;
    return $timestamp;
}

function grinco_validate_form_timing($formType, $postedTimestamp)
{
    $config = grinco_form_security_config();
    $sessionTimestamp = isset($_SESSION['grinco_form_opened_' . $formType])
        ? (int) $_SESSION['grinco_form_opened_' . $formType]
        : 0;
    $postedTimestamp = is_scalar($postedTimestamp) && ctype_digit((string) $postedTimestamp)
        ? (int) $postedTimestamp
        : 0;
    $elapsed = time() - $sessionTimestamp;

    if ($sessionTimestamp <= 0 || $postedTimestamp !== $sessionTimestamp) {
        return array('valid' => false, 'reason' => 'invalid_form_timestamp', 'elapsed' => 0);
    }
    if ($elapsed < (int) $config['timing']['minimum_seconds']) {
        return array('valid' => false, 'reason' => 'submitted_too_fast', 'elapsed' => $elapsed);
    }
    if ($elapsed > (int) $config['timing']['maximum_seconds']) {
        return array('valid' => false, 'reason' => 'form_expired', 'elapsed' => $elapsed);
    }

    return array('valid' => true, 'reason' => '', 'elapsed' => $elapsed);
}

function grinco_post_value($name)
{
    return isset($_POST[$name]) && is_scalar($_POST[$name]) ? (string) $_POST[$name] : '';
}

function grinco_normalize_text($value, $multiline)
{
    $value = str_replace("\0", '', (string) $value);
    if (preg_match('//u', $value) !== 1) {
        return '';
    }
    $value = preg_replace('/\r\n?/', "\n", $value);

    if ($multiline) {
        $value = preg_replace('/[ \t]+/u', ' ', $value);
        $value = preg_replace('/\n{3,}/u', "\n\n", $value);
    } else {
        $value = preg_replace('/\s+/u', ' ', $value);
    }

    return trim($value);
}

function grinco_has_forbidden_control_characters($value, $allowNewlines)
{
    $pattern = $allowNewlines
        ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/'
        : '/[\x00-\x1F\x7F]/';
    return preg_match($pattern, (string) $value) === 1;
}

function grinco_detect_header_injection($value)
{
    return preg_match('/(?:\r|\n|bcc\s*:|cc\s*:|content-type\s*:|mime-version\s*:|reply-to\s*:)/i', (string) $value) === 1;
}

function grinco_validate_name($value, $minimumLength, $maximumLength)
{
    $rawValue = (string) $value;
    $value = grinco_normalize_text(strip_tags($rawValue), false);
    $errors = array();

    if ($value === '' || grinco_utf8_length($value) < $minimumLength) {
        $errors[] = 'Veuillez indiquer un nom complet valide.';
    } elseif (grinco_utf8_length($value) > $maximumLength) {
        $errors[] = 'Le nom complet dépasse la longueur autorisée.';
    } elseif (grinco_has_forbidden_control_characters($rawValue, false)) {
        $errors[] = 'Le nom complet contient des caractères non autorisés.';
    } elseif (!preg_match("/^[\\p{L}\\p{M}][\\p{L}\\p{M} '’\\-]*$/u", $value)) {
        $errors[] = 'Le nom complet contient des caractères non autorisés.';
    } elseif (preg_match('/^\d+$/', $value) || grinco_count_urls($value) > 0) {
        $errors[] = 'Veuillez indiquer un nom complet valide.';
    }

    return array('value' => $value, 'errors' => $errors);
}

function grinco_email_suspicion_score($email)
{
    $config = grinco_form_security_config();
    $parts = explode('@', strtolower(trim((string) $email)));
    $domain = count($parts) === 2 ? end($parts) : '';

    if ($domain !== '' && in_array($domain, $config['spam']['suspicious_email_domains'], true)) {
        return 2;
    }

    return 0;
}

function grinco_validate_email_address($value)
{
    $value = trim((string) $value);
    $errors = array();
    $dnsStatus = 'not_checked';

    if ($value === '') {
        $errors[] = 'L’adresse e-mail est obligatoire.';
    } elseif (strlen($value) > 190) {
        $errors[] = 'L’adresse e-mail dépasse la longueur autorisée.';
    } elseif (grinco_detect_header_injection($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L’adresse e-mail n’est pas valide.';
    } else {
        $parts = explode('@', $value);
        $domain = strtolower(end($parts));
        if (
            strpos($domain, '.') === false
            || preg_match('/(?:^|\.)(?:invalid|localhost|test|example)$/i', $domain)
        ) {
            $errors[] = 'Le domaine de l’adresse e-mail n’est pas valide.';
        } elseif (function_exists('checkdnsrr')) {
            $dnsStatus = (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')) ? 'valid' : 'unavailable';
        }
    }

    return array('value' => $value, 'errors' => $errors, 'dns_status' => $dnsStatus);
}

function grinco_validate_phone($value, $required)
{
    $value = grinco_normalize_text(strip_tags((string) $value), false);
    $errors = array();

    if ($value === '') {
        if ($required) {
            $errors[] = 'Le numéro de téléphone est obligatoire.';
        }
    } elseif (
        strlen($value) < 7
        || strlen($value) > 25
        || !preg_match('/^[0-9+()\s-]+$/', $value)
        || strlen(preg_replace('/\D+/', '', $value)) < 6
    ) {
        $errors[] = 'Le numéro de téléphone n’est pas valide.';
    }

    return array('value' => $value, 'errors' => $errors);
}

function grinco_count_urls($text)
{
    $text = (string) $text;
    $pattern = '~(?:(?:https?://|www\.)[^\s<>"\']+|\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:com|net|org|info|biz|io|co|me|tv|app|dev|cloud|online|site|xyz|top|link|news|pro|fr|cd|cn|ru|uk|us|de|be|ch|ca|au|za)\b(?:/[^\s<>"\']*)?)~iu';
    preg_match_all($pattern, $text, $matches);
    return isset($matches[0]) ? count($matches[0]) : 0;
}

function grinco_extract_domains($text)
{
    preg_match_all(
        '~(?:https?://|www\.)?((?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,24}))~iu',
        (string) $text,
        $matches
    );
    $domains = array();

    if (!empty($matches[1])) {
        foreach ($matches[1] as $domain) {
            $domains[] = strtolower(rtrim($domain, '.'));
        }
    }

    return array_values(array_unique($domains));
}

function grinco_contains_any($text, $needles)
{
    $text = mb_strtolower((string) $text, 'UTF-8');
    foreach ($needles as $needle) {
        if ($needle !== '' && mb_strpos($text, mb_strtolower($needle, 'UTF-8'), 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}

function grinco_analyze_spam($formType, $fields, $subject)
{
    $config = grinco_form_security_config();
    $combinedText = implode("\n", array_values($fields));
    $urlCount = grinco_count_urls($combinedText);
    $domains = grinco_extract_domains($combinedText);
    $score = 0;
    $reason = '';

    if (grinco_detect_header_injection($subject)) {
        return array('rejected' => true, 'score' => 100, 'url_count' => $urlCount, 'reason' => 'header_injection');
    }
    if ($subject !== '' && grinco_count_urls($subject) > 0) {
        return array('rejected' => true, 'score' => 100, 'url_count' => $urlCount, 'reason' => 'url_in_subject');
    }

    $dangerousPattern = '/(?:javascript\s*:|data\s*:|vbscript\s*:|file\s*:|ftp\s*:|<\s*(?:script|iframe|object|embed)\b|onerror\s*=|onclick\s*=|document\.cookie|eval\s*\(|base64_decode\s*\(|<\?php|php:\/\/)/iu';
    if (preg_match($dangerousPattern, $combinedText)) {
        return array('rejected' => true, 'score' => 100, 'url_count' => $urlCount, 'reason' => 'dangerous_content');
    }
    if (preg_match('/<[^>]+>/', $combinedText)) {
        return array('rejected' => true, 'score' => 100, 'url_count' => $urlCount, 'reason' => 'html_content');
    }
    if (preg_match('/(?:union\s+select|drop\s+table|insert\s+into|delete\s+from|or\s+1\s*=\s*1|--\s*$)/iu', $combinedText)) {
        return array('rejected' => true, 'score' => 100, 'url_count' => $urlCount, 'reason' => 'injection_pattern');
    }

    $maximumUrls = isset($config['spam']['maximum_urls'][$formType])
        ? (int) $config['spam']['maximum_urls'][$formType]
        : 1;
    if ($urlCount > $maximumUrls) {
        $score += 5;
        $reason = 'too_many_urls';
    }

    $riskyDomainCount = 0;
    foreach ($domains as $domain) {
        foreach ($config['spam']['risky_domains'] as $riskyDomain) {
            if ($domain === $riskyDomain || substr($domain, -strlen('.' . $riskyDomain)) === '.' . $riskyDomain) {
                $riskyDomainCount++;
                break;
            }
        }
    }
    if ($riskyDomainCount > 0) {
        $score += 3;
        $reason = $reason === '' ? 'risky_domain' : $reason;
    }
    if ($riskyDomainCount > 0 && ($urlCount > 1 || grinco_utf8_length($combinedText) < 140)) {
        $score += 3;
    }

    if (count($domains) >= 4) {
        $score += 5;
        $reason = 'mass_domain_list';
    }
    if (preg_match_all('/(?:^|\s)@[a-z0-9_]{3,}/iu', $combinedText, $handleMatches) >= 5) {
        $score += 5;
        $reason = 'mass_handle_list';
    }
    if (grinco_contains_any($combinedText, $config['spam']['violent_keywords'])) {
        $score += 5;
        $reason = 'violent_content';
    }
    if (grinco_contains_any($combinedText, $config['spam']['campaign_keywords'])) {
        $score += 5;
        $reason = 'campaign_spam';
    }
    if ($urlCount > 0 && grinco_utf8_length($combinedText) < (80 * $urlCount)) {
        $score += 2;
        $reason = $reason === '' ? 'high_link_ratio' : $reason;
    }
    if (preg_match('/\b(\p{L}{4,})\b(?:[\s,;:!?-]+\1){5,}/iu', $combinedText)) {
        $score += 3;
        $reason = $reason === '' ? 'abnormal_repetition' : $reason;
    }

    return array(
        'rejected' => $score >= (int) $config['spam']['reject_score'],
        'score' => $score,
        'url_count' => $urlCount,
        'reason' => $reason === '' ? 'accepted' : $reason
    );
}

function grinco_client_ip()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function grinco_mask_ip($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        $parts[3] = '0';
        return implode('.', $parts);
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 4)) . '::';
    }
    return 'unknown';
}

function grinco_request_user_agent_hash()
{
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? trim((string) $_SERVER['HTTP_USER_AGENT']) : '';
    return $userAgent === '' ? '' : hash('sha256', $userAgent);
}

function grinco_validate_request_origin()
{
    $config = grinco_form_security_config();
    $candidates = array();
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $candidates[] = (string) $_SERVER['HTTP_ORIGIN'];
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $candidates[] = (string) $_SERVER['HTTP_REFERER'];
    }

    foreach ($candidates as $candidate) {
        $host = strtolower((string) parse_url($candidate, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        $allowed = false;
        foreach ($config['allowed_hosts'] as $allowedHost) {
            if ($host === strtolower($allowedHost)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return false;
        }
    }

    return true;
}

function grinco_security_storage_path()
{
    $config = grinco_form_security_config();
    $path = isset($config['storage']['path']) ? trim((string) $config['storage']['path']) : '';
    if ($path === '') {
        $path = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'grinco-form-security';
    }

    if (!is_dir($path)) {
        @mkdir($path, 0700, true);
    }
    if (is_dir($path)) {
        @chmod($path, 0700);
    }

    return $path;
}

function grinco_rate_file($formType, $kind, $identifier)
{
    $directory = grinco_security_storage_path() . DIRECTORY_SEPARATOR . 'rate';
    if (!is_dir($directory)) {
        @mkdir($directory, 0700, true);
    }
    return $directory . DIRECTORY_SEPARATOR . $formType . '-' . $kind . '-' . hash('sha256', $identifier) . '.json';
}

function grinco_rate_limit_consume_identifier($formType, $kind, $identifier)
{
    $config = grinco_form_security_config();
    $path = grinco_rate_file($formType, $kind, $identifier);
    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        return array('allowed' => false, 'reason' => 'rate_storage_unavailable');
    }

    flock($handle, LOCK_EX);
    $contents = stream_get_contents($handle);
    $timestamps = json_decode($contents, true);
    $timestamps = is_array($timestamps) ? $timestamps : array();
    $now = time();
    $validTimestamps = array();

    foreach ($timestamps as $timestamp) {
        if (is_numeric($timestamp) && (int) $timestamp > ($now - 86400)) {
            $validTimestamps[] = (int) $timestamp;
        }
    }

    $allowed = true;
    $reason = '';
    foreach ($config['rate_limits'] as $limitName => $limit) {
        $count = 0;
        foreach ($validTimestamps as $timestamp) {
            if ($timestamp > ($now - (int) $limit['window'])) {
                $count++;
            }
        }
        if ($count >= (int) $limit['maximum']) {
            $allowed = false;
            $reason = 'rate_limit_' . $limitName;
            break;
        }
    }

    if ($allowed) {
        $validTimestamps[] = $now;
    }

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, grinco_json_encode($validTimestamps));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    @chmod($path, 0600);

    return array('allowed' => $allowed, 'reason' => $reason);
}

function grinco_check_rate_limit($formType, $ipAddress, $email)
{
    $ipResult = grinco_rate_limit_consume_identifier($formType, 'ip', $ipAddress);
    if (!$ipResult['allowed']) {
        return $ipResult;
    }

    if ($email !== '') {
        $emailResult = grinco_rate_limit_consume_identifier($formType, 'email', strtolower($email));
        if (!$emailResult['allowed']) {
            return $emailResult;
        }
    }

    return array('allowed' => true, 'reason' => '');
}

function grinco_purge_security_files()
{
    if (mt_rand(1, 100) !== 1) {
        return;
    }

    $config = grinco_form_security_config();
    $root = grinco_security_storage_path();
    $directories = array(
        $root => time() - ((int) $config['storage']['log_retention_days'] * 86400),
        $root . DIRECTORY_SEPARATOR . 'rate' => time() - (int) $config['storage']['rate_retention_seconds']
    );

    foreach ($directories as $directory => $cutoff) {
        if (!is_dir($directory)) {
            continue;
        }
        $files = glob($directory . DIRECTORY_SEPARATOR . '*');
        if (!is_array($files)) {
            continue;
        }
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}

function grinco_security_log($formType, $status, $reason, $score, $urlCount, $email, $requestId)
{
    $root = grinco_security_storage_path();
    $path = $root . DIRECTORY_SEPARATOR . 'security-' . date('Y-m-d') . '.log';
    $safeReason = preg_replace('/[\r\n\x00-\x1F\x7F]+/', ' ', (string) $reason);
    $entry = array(
        'timestamp' => gmdate('c'),
        'form' => $formType,
        'ip' => grinco_mask_ip(grinco_client_ip()),
        'email_hash' => $email === '' ? '' : hash('sha256', strtolower(trim($email))),
        'user_agent_hash' => grinco_request_user_agent_hash(),
        'status' => $status,
        'reason' => $safeReason,
        'spam_score' => (int) $score,
        'url_count' => (int) $urlCount,
        'request_id' => preg_replace('/[^a-z0-9]/i', '', (string) $requestId)
    );

    $handle = @fopen($path, 'ab');
    if ($handle !== false) {
        flock($handle, LOCK_EX);
        fwrite($handle, grinco_json_encode($entry) . PHP_EOL);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        @chmod($path, 0600);
    }

    grinco_purge_security_files();
}

function grinco_turnstile_is_enabled()
{
    $config = grinco_form_security_config();
    return !empty($config['turnstile']['enabled'])
        && !empty($config['turnstile']['site_key'])
        && !empty($config['turnstile']['secret_key']);
}

function grinco_turnstile_site_key()
{
    $config = grinco_form_security_config();
    return isset($config['turnstile']['site_key']) ? (string) $config['turnstile']['site_key'] : '';
}

function grinco_validate_turnstile($responseToken, $remoteIp)
{
    if (!grinco_turnstile_is_enabled()) {
        return array('valid' => true, 'reason' => 'disabled');
    }

    if (!is_string($responseToken) || trim($responseToken) === '') {
        return array('valid' => false, 'reason' => 'missing_token');
    }

    $config = grinco_form_security_config();
    $postData = http_build_query(array(
        'secret' => (string) $config['turnstile']['secret_key'],
        'response' => trim($responseToken),
        'remoteip' => $remoteIp
    ));
    $rawResponse = false;

    if (function_exists('curl_init')) {
        $curl = curl_init((string) $config['turnstile']['verify_url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, (int) $config['turnstile']['timeout_seconds']);
        $rawResponse = curl_exec($curl);
        curl_close($curl);
    } elseif (ini_get('allow_url_fopen')) {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => (int) $config['turnstile']['timeout_seconds']
            )
        ));
        $rawResponse = @file_get_contents((string) $config['turnstile']['verify_url'], false, $context);
    }

    if ($rawResponse === false) {
        return array('valid' => false, 'reason' => 'verification_unavailable');
    }

    $decoded = json_decode($rawResponse, true);
    return array(
        'valid' => is_array($decoded) && !empty($decoded['success']),
        'reason' => is_array($decoded) && !empty($decoded['success']) ? 'valid' : 'verification_failed'
    );
}

function grinco_set_form_flash($formType, $type, $message, $errors, $fieldErrors)
{
    $prefix = $formType === 'quote' ? 'quote' : 'contact';
    unset(
        $_SESSION[$prefix . '_success'],
        $_SESSION[$prefix . '_error'],
        $_SESSION[$prefix . '_validation_errors'],
        $_SESSION[$prefix . '_field_errors']
    );

    $_SESSION[$prefix . ($type === 'success' ? '_success' : '_error')] = $message;
    if (!empty($errors)) {
        $_SESSION[$prefix . '_validation_errors'] = array_values(array_unique($errors));
    }
    if (!empty($fieldErrors)) {
        $_SESSION[$prefix . '_field_errors'] = $fieldErrors;
    }
}

function grinco_finalize_form_attempt($formType)
{
    unset($_SESSION['grinco_form_opened_' . $formType]);
    grinco_regenerate_csrf_token($formType);
}

function grinco_redirect_form($formType)
{
    $location = grinco_url($formType === 'quote' ? '/demande-devis' : '/contact');
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . $location);
    exit;
}

function grinco_email_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function grinco_email_value($value)
{
    return $value === ''
        ? '<span style="color:#8A949E;">Non renseigné</span>'
        : nl2br(grinco_email_escape($value));
}

function grinco_email_row($label, $value)
{
    return '<tr>'
        . '<td width="34%" valign="top" style="padding:11px 14px;border-bottom:1px solid #E8ECE9;color:#5F6B76;font-size:13px;font-weight:600;">' . grinco_email_escape($label) . '</td>'
        . '<td valign="top" style="padding:11px 14px;border-bottom:1px solid #E8ECE9;color:#1F2933;font-size:14px;line-height:1.55;">' . grinco_email_value($value) . '</td>'
        . '</tr>';
}
