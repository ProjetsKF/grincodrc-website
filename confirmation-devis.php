<?php
require_once __DIR__ . '/includes/form-security.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$token = isset($_GET['reference']) && is_scalar($_GET['reference']) ? (string) $_GET['reference'] : '';
$confirmation = isset($_SESSION['quote_confirmation']) && is_array($_SESSION['quote_confirmation']) ? $_SESSION['quote_confirmation'] : array();
$validConfirmation = $token !== '' && !empty($confirmation['token']) && grinco_hash_equals((string) $confirmation['token'], $token);
if (!$validConfirmation) {
    header('Location: ' . grinco_url('/catalogue'));
    exit;
}
unset($_SESSION['quote_confirmation']);
$reference = 'DEVIS-' . str_pad((string) (int) $confirmation['request_id'], 6, '0', STR_PAD_LEFT);

$pageTitle = 'Demande enregistrée';
$pageDescription = 'Confirmation de votre demande de devis GRINCO RDC.';
$currentPage = 'demande-devis';
$bodyClass = 'quote-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main"><section class="quote-confirmation-section section"><div class="container"><div class="quote-confirmation-card"><span><i class="bi bi-check-circle-fill" aria-hidden="true"></i></span><h1>Votre demande de devis a bien été enregistrée.</h1><p>Notre équipe vous contactera prochainement.</p><div class="quote-confirmation-reference"><small>Référence de votre demande</small><strong><?php echo htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'); ?></strong></div><a class="catalogue-add-button" href="<?php echo grinco_url_html('/catalogue'); ?>">Retour au catalogue</a></div></div></section></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
