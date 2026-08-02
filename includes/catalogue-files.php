<?php

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/config/app.php';

function grinco_catalogue_project_root()
{
    return dirname(__DIR__);
}

function grinco_catalogue_file_config($type)
{
    $types = array(
        'image' => array(
            'directory' => grinco_catalogue_project_root() . '/uploads/catalogue/images',
            'relative_prefix' => 'uploads/catalogue/images/',
            'maximum_bytes' => 2 * 1024 * 1024,
            'extensions' => array('jpg', 'jpeg', 'png', 'webp'),
            'mimes' => array(
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/x-webp' => 'webp'
            )
        ),
        'document' => array(
            'directory' => grinco_catalogue_project_root() . '/uploads/catalogue/documents',
            'relative_prefix' => 'uploads/catalogue/documents/',
            'maximum_bytes' => 2 * 1024 * 1024,
            'extensions' => array('pdf'),
            'mimes' => array(
                'application/pdf' => 'pdf',
                'application/x-pdf' => 'pdf'
            )
        )
    );

    if (!isset($types[$type])) {
        throw new InvalidArgumentException('Type de fichier non pris en charge.');
    }

    return $types[$type];
}

function grinco_catalogue_ensure_directory($type)
{
    $config = grinco_catalogue_file_config($type);
    if (!is_dir($config['directory']) && !@mkdir($config['directory'], 0755, true)) {
        throw new RuntimeException('Le dossier de stockage n’est pas disponible.');
    }
    if (!is_writable($config['directory'])) {
        throw new RuntimeException('Le dossier de stockage n’est pas accessible en écriture.');
    }
    return $config['directory'];
}

function grinco_catalogue_normalize_uploads($input)
{
    if (!is_array($input) || !isset($input['name'], $input['tmp_name'], $input['error'], $input['size'])) {
        return array();
    }

    if (!is_array($input['name'])) {
        return array($input);
    }

    $files = array();
    $count = count($input['name']);
    for ($index = 0; $index < $count; $index++) {
        $files[] = array(
            'name' => isset($input['name'][$index]) ? $input['name'][$index] : '',
            'type' => isset($input['type'][$index]) ? $input['type'][$index] : '',
            'tmp_name' => isset($input['tmp_name'][$index]) ? $input['tmp_name'][$index] : '',
            'error' => isset($input['error'][$index]) ? $input['error'][$index] : UPLOAD_ERR_NO_FILE,
            'size' => isset($input['size'][$index]) ? $input['size'][$index] : 0
        );
    }
    return $files;
}

function grinco_catalogue_upload_error_message($error)
{
    $messages = array(
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite autorisée par le serveur.',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite autorisée.',
        UPLOAD_ERR_PARTIAL => 'Le transfert du fichier est incomplet.',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n’a été sélectionné.',
        UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire du serveur est indisponible.',
        UPLOAD_ERR_CANT_WRITE => 'Le serveur ne peut pas enregistrer le fichier.',
        UPLOAD_ERR_EXTENSION => 'Le transfert a été interrompu par le serveur.'
    );
    return isset($messages[$error]) ? $messages[$error] : 'Le fichier n’a pas pu être transféré.';
}

function grinco_catalogue_validate_upload($file, $type)
{
    $config = grinco_catalogue_file_config($type);
    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        return array('valid' => false, 'message' => grinco_catalogue_upload_error_message($error));
    }

    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($size <= 0 || $size > $config['maximum_bytes']) {
        return array('valid' => false, 'message' => 'Chaque fichier doit peser au maximum 2 Mo.');
    }

    $temporaryPath = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        return array('valid' => false, 'message' => 'Le fichier transféré n’est pas valide.');
    }

    $originalName = isset($file['name']) ? (string) $file['name'] : '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $config['extensions'], true)) {
        return array('valid' => false, 'message' => 'L’extension du fichier n’est pas autorisée.');
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException('La vérification du type de fichier est indisponible.');
    }
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $fileInfo->file($temporaryPath);
    if ($type === 'image' && $extension === 'webp' && $mime === 'application/octet-stream') {
        $handle = @fopen($temporaryPath, 'rb');
        $signature = $handle ? fread($handle, 12) : '';
        if ($handle) {
            fclose($handle);
        }
        if (strlen($signature) === 12 && substr($signature, 0, 4) === 'RIFF' && substr($signature, 8, 4) === 'WEBP') {
            $mime = 'image/webp';
        }
    }
    if (!isset($config['mimes'][$mime])) {
        return array('valid' => false, 'message' => 'Le contenu réel du fichier ne correspond pas au format autorisé.');
    }

    return array(
        'valid' => true,
        'message' => '',
        'extension' => $config['mimes'][$mime],
        'mime' => $mime
    );
}

function grinco_catalogue_random_filename($extension, $directory)
{
    for ($attempt = 0; $attempt < 10; $attempt++) {
        if (function_exists('grinco_random_token')) {
            $token = substr(grinco_random_token(), 0, 40);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes(20));
        } else {
            $token = hash('sha256', uniqid(mt_rand(), true));
        }
        $filename = $token . '.' . $extension;
        if (!file_exists($directory . DIRECTORY_SEPARATOR . $filename)) {
            return $filename;
        }
    }
    throw new RuntimeException('Impossible de générer un nom de fichier sécurisé.');
}

function grinco_catalogue_secure_token()
{
    if (function_exists('grinco_random_token')) {
        return grinco_random_token();
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
    return hash('sha256', uniqid(mt_rand(), true));
}

function grinco_catalogue_store_upload($file, $type, $validation)
{
    $config = grinco_catalogue_file_config($type);
    $directory = grinco_catalogue_ensure_directory($type);
    $filename = grinco_catalogue_random_filename($validation['extension'], $directory);
    $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Le fichier n’a pas pu être enregistré.');
    }
    @chmod($absolutePath, 0644);

    return array(
        'filename' => $filename,
        'stored_path' => $config['relative_prefix'] . $filename,
        'absolute_path' => $absolutePath
    );
}

function grinco_catalogue_resolve_stored_file($storedPath, $type)
{
    $config = grinco_catalogue_file_config($type);
    $storedPath = str_replace('\\', '/', trim((string) $storedPath));
    if ($storedPath === '' || strpos($storedPath, "\0") !== false) {
        return false;
    }

    $filename = basename($storedPath);
    $isFilenameOnly = $storedPath === $filename;
    $isExpectedRelativePath = $storedPath === $config['relative_prefix'] . $filename;
    if (!$isFilenameOnly && !$isExpectedRelativePath) {
        return false;
    }
    if (!preg_match('/^[a-f0-9]{32,64}\.(?:jpg|png|webp|pdf)$/i', $filename)) {
        return false;
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedFinalExtensions = array_values(array_unique(array_values($config['mimes'])));
    if (!in_array($extension, $allowedFinalExtensions, true)) {
        return false;
    }

    $directory = $config['directory'];
    if (!is_dir($directory)) {
        return false;
    }
    $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
    return array(
        'filename' => $filename,
        'stored_path' => $config['relative_prefix'] . $filename,
        'absolute_path' => $absolutePath,
        'exists' => is_file($absolutePath)
    );
}

function grinco_catalogue_file_url($storedPath, $type)
{
    $resolved = grinco_catalogue_resolve_stored_file($storedPath, $type);
    if ($resolved === false || !$resolved['exists']) {
        return '';
    }
    return grinco_url('/' . $resolved['stored_path']);
}

function grinco_catalogue_stage_file_deletion($storedPath, $type)
{
    $resolved = grinco_catalogue_resolve_stored_file($storedPath, $type);
    if ($resolved === false) {
        throw new RuntimeException('Le chemin du fichier n’est pas autorisé.');
    }
    if (!$resolved['exists']) {
        return array('original' => '', 'staged' => '');
    }

    $stagedPath = $resolved['absolute_path'] . '.delete-' . substr(grinco_catalogue_secure_token(), 0, 16) . '.tmp';
    if (!@rename($resolved['absolute_path'], $stagedPath)) {
        throw new RuntimeException('Le fichier physique ne peut pas être préparé pour la suppression.');
    }
    return array('original' => $resolved['absolute_path'], 'staged' => $stagedPath);
}

function grinco_catalogue_restore_staged_file($staged)
{
    if (!empty($staged['staged']) && is_file($staged['staged'])) {
        @rename($staged['staged'], $staged['original']);
    }
}

function grinco_catalogue_finalize_staged_file($staged)
{
    if (!empty($staged['staged']) && is_file($staged['staged'])) {
        return @unlink($staged['staged']);
    }
    return true;
}

function grinco_catalogue_post_maximum_bytes()
{
    $value = trim((string) ini_get('post_max_size'));
    if ($value === '') {
        return 0;
    }
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === 'g') {
        $number *= 1024;
    }
    if ($unit === 'g' || $unit === 'm') {
        $number *= 1024;
    }
    if ($unit === 'g' || $unit === 'm' || $unit === 'k') {
        $number *= 1024;
    }
    return (int) $number;
}
