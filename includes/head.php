<?php
require_once dirname(__DIR__) . '/config/app.php';

$pageTitle = isset($pageTitle) ? $pageTitle : 'GRINCO RDC';
$pageDescription = isset($pageDescription) ? $pageDescription : 'GRINCO RDC, spécialiste des véhicules, engins lourds, pièces de rechange et services d’ingénierie.';
$currentPage = isset($currentPage) ? $currentPage : '';
$bodyClass = isset($bodyClass) ? $bodyClass : 'inner-page';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | GRINCO RDC</title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">

  <link href="<?php echo grinco_url_html('/assets/img/ico.png'); ?>" rel="icon">
  <link href="<?php echo grinco_url_html('/assets/img/ico.png'); ?>" rel="logo grinco">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@500;600;700&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">

  <link href="<?php echo grinco_url_html('/assets/vendor/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/vendor/bootstrap-icons/bootstrap-icons.css'); ?>" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/vendor/aos/aos.css'); ?>" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/vendor/swiper/swiper-bundle.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo grinco_url_html('/assets/css/main.css'); ?>" rel="stylesheet">
</head>

<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
