<?php
require_once dirname(dirname(__DIR__)) . '/config/app.php';

$adminPageTitle = isset($adminPageTitle) ? $adminPageTitle : 'Administration';
$adminPageDescription = isset($adminPageDescription)
    ? $adminPageDescription
    : 'Espace d’administration GRINCO RDC.';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo grinco_admin_escape($adminPageDescription); ?>">
  <meta name="robots" content="noindex, nofollow">
  <title><?php echo grinco_admin_escape($adminPageTitle); ?> | Administration GRINCO RDC</title>

  <link href="<?php echo grinco_url_html('/assets/img/ico.png'); ?>" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/vendor/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/css/admin.css'); ?>" rel="stylesheet">
</head>

<body class="admin-page">
