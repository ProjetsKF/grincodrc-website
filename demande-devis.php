<?php
require_once __DIR__ . '/includes/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

try {
    $quoteCartData = grinco_quote_cart_items();
} catch (PDOException $exception) {
    error_log('[GRINCO quote form] Unable to load cart.');
    grinco_quote_cart_set_flash('error', 'Votre sélection ne peut pas être chargée pour le moment.');
    header('Location: ' . grinco_url('/panier-devis'));
    exit;
}
if (empty($quoteCartData['items']) || !empty($quoteCartData['missing_ids'])) {
    grinco_quote_cart_set_flash('error', 'Ajoutez au moins un produit valide avant de remplir la demande.');
    header('Location: ' . grinco_url('/panier-devis'));
    exit;
}

$quoteCsrfToken = grinco_csrf_token('quote');
$quoteFormStartedAt = grinco_mark_form_opened('quote');
$quoteTurnstileEnabled = grinco_turnstile_is_enabled();
$quoteTurnstileSiteKey = grinco_turnstile_site_key();
if (empty($_SESSION['quote_submission_token']) || !is_string($_SESSION['quote_submission_token'])) {
    $_SESSION['quote_submission_token'] = grinco_random_token();
}
$quoteSubmissionToken = $_SESSION['quote_submission_token'];

$quoteError = isset($_SESSION['quote_error']) ? (string) $_SESSION['quote_error'] : '';
$quoteValidationErrors = isset($_SESSION['quote_validation_errors']) && is_array($_SESSION['quote_validation_errors']) ? $_SESSION['quote_validation_errors'] : array();
$quoteFieldErrors = isset($_SESSION['quote_field_errors']) && is_array($_SESSION['quote_field_errors']) ? $_SESSION['quote_field_errors'] : array();
$quoteOld = isset($_SESSION['quote_old']) && is_array($_SESSION['quote_old']) ? $_SESSION['quote_old'] : array();
unset($_SESSION['quote_error'], $_SESSION['quote_validation_errors'], $_SESSION['quote_field_errors'], $_SESSION['quote_old'], $_SESSION['quote_success']);

function quote_old_value($old, $field)
{
    return isset($old[$field]) && is_scalar($old[$field]) ? htmlspecialchars((string) $old[$field], ENT_QUOTES, 'UTF-8') : '';
}
function quote_invalid_attribute($errors, $field)
{
    return !empty($errors[$field]) ? ' aria-invalid="true"' : '';
}
function quote_field_error($errors, $field)
{
    return empty($errors[$field]) ? '' : '<div class="form-security-field-error">' . htmlspecialchars((string) $errors[$field], ENT_QUOTES, 'UTF-8') . '</div>';
}

$pageTitle = 'Demande de devis';
$pageDescription = 'Transmettez à GRINCO RDC une demande regroupant tous les produits sélectionnés.';
$currentPage = 'demande-devis';
$bodyClass = 'quote-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Demande de devis</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li><a href="<?php echo grinco_url_html('/panier-devis'); ?>">Ma sélection</a></li><li class="current">Coordonnées</li></ol></nav></div></div>
  <section class="quote-form-section section"><div class="container">
    <?php if ($quoteError !== ''): ?><div class="alert alert-danger quote-alert quote-alert-error" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><div><strong>Demande non enregistrée</strong><p><?php echo htmlspecialchars($quoteError, ENT_QUOTES, 'UTF-8'); ?></p><?php if ($quoteValidationErrors): ?><ul><?php foreach ($quoteValidationErrors as $error): ?><li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul><?php endif; ?></div></div><?php endif; ?>
    <div class="row gy-4"><div class="col-lg-8">
      <section class="quote-form-card quote-products-summary" aria-labelledby="quote-products-title"><div class="quote-form-card-header"><div class="quote-form-card-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><div><span>Votre sélection</span><h2 id="quote-products-title">Produits demandés</h2></div></div><div class="table-responsive"><table><thead><tr><th>Référence</th><th>Produit</th><th>Quantité</th></tr></thead><tbody><?php foreach ($quoteCartData['items'] as $item): ?><tr><td><?php echo htmlspecialchars($item['reference'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['nom'] . ($item['modele'] !== '' ? ' — ' . $item['modele'] : ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $item['quantite']; ?></td></tr><?php endforeach; ?></tbody></table></div><a href="<?php echo grinco_url_html('/panier-devis'); ?>" class="quote-edit-selection"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier la sélection</a></section>

      <form action="<?php echo grinco_url_html('/traitement-demande-devis'); ?>" method="POST" class="quote-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($quoteCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="form_started_at" value="<?php echo (int) $quoteFormStartedAt; ?>"><input type="hidden" name="submission_token" value="<?php echo htmlspecialchars($quoteSubmissionToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="quote-honeypot" aria-hidden="true"><label for="quote-website">Votre site</label><input id="quote-website" name="website" type="text" tabindex="-1" autocomplete="off"><label for="quote-company-url">URL entreprise</label><input id="quote-company-url" name="company_url" type="text" tabindex="-1" autocomplete="off"></div>
        <section class="quote-form-card" aria-labelledby="client-information-title"><div class="quote-form-card-header"><div class="quote-form-card-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></div><div><span>Coordonnées</span><h2 id="client-information-title">Informations du client</h2></div></div><div class="row g-4">
          <div class="col-md-6"><label for="nom" class="quote-label">Nom complet <span aria-hidden="true">*</span></label><input type="text" id="nom" name="nom" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'nom'); ?>" maxlength="100" autocomplete="name" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'nom'); ?>><?php echo quote_field_error($quoteFieldErrors, 'nom'); ?></div>
          <div class="col-md-6"><label for="entreprise" class="quote-label">Entreprise</label><input type="text" id="entreprise" name="entreprise" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'entreprise'); ?>" maxlength="150" autocomplete="organization"<?php echo quote_invalid_attribute($quoteFieldErrors, 'entreprise'); ?>><?php echo quote_field_error($quoteFieldErrors, 'entreprise'); ?></div>
          <div class="col-md-6"><label for="telephone" class="quote-label">Téléphone <span aria-hidden="true">*</span></label><input type="tel" id="telephone" name="telephone" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'telephone'); ?>" maxlength="30" autocomplete="tel" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'telephone'); ?>><?php echo quote_field_error($quoteFieldErrors, 'telephone'); ?></div>
          <div class="col-md-6"><label for="email" class="quote-label">Adresse e-mail</label><input type="email" id="email" name="email" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'email'); ?>" maxlength="150" autocomplete="email"<?php echo quote_invalid_attribute($quoteFieldErrors, 'email'); ?>><?php echo quote_field_error($quoteFieldErrors, 'email'); ?></div>
          <div class="col-12"><label for="message" class="quote-label">Message</label><textarea id="message" name="message" class="quote-control" rows="6" maxlength="5000"<?php echo quote_invalid_attribute($quoteFieldErrors, 'message'); ?>><?php echo quote_old_value($quoteOld, 'message'); ?></textarea><?php echo quote_field_error($quoteFieldErrors, 'message'); ?></div>
        </div></section>
        <div class="quote-consent"><input type="checkbox" id="consent" name="consent" value="1"<?php echo !empty($quoteOld['consent']) ? ' checked' : ''; ?> required<?php echo quote_invalid_attribute($quoteFieldErrors, 'consent'); ?>><label for="consent">J’accepte que mes informations soient utilisées pour traiter ma demande. <span aria-hidden="true">*</span></label></div><?php echo quote_field_error($quoteFieldErrors, 'consent'); ?>
        <?php if ($quoteTurnstileEnabled): ?><div class="form-security-turnstile"><div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($quoteTurnstileSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div></div><?php endif; ?>
        <div class="quote-submit-row"><p><span aria-hidden="true">*</span> Champs obligatoires</p><button type="submit" class="quote-submit-btn"><span>Envoyer ma demande</span><i class="bi bi-send" aria-hidden="true"></i></button></div>
      </form>
    </div><aside class="col-lg-4"><div class="quote-sidebar"><div class="quote-sidebar-card"><span class="quote-sidebar-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span><h3>Demande sécurisée</h3><p>Votre sélection sera enregistrée en une seule demande. Notre équipe vous contactera pour préciser les disponibilités et les conditions.</p><ul><li><?php echo count($quoteCartData['items']); ?> produit<?php echo count($quoteCartData['items']) === 1 ? '' : 's'; ?> sélectionné<?php echo count($quoteCartData['items']) === 1 ? '' : 's'; ?></li><li>Aucun prix transmis publiquement</li><li>Validation par notre équipe commerciale</li></ul></div></div></aside></div>
  </div></section>
</main>
<?php if ($quoteTurnstileEnabled): ?><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
