<?php
require_once __DIR__ . '/includes/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$hasRequestedProduct = isset($_GET['produit']);
$requestedProductId = $hasRequestedProduct && ctype_digit((string) $_GET['produit']) && (int) $_GET['produit'] > 0
    ? (int) $_GET['produit']
    : 0;

if ($hasRequestedProduct && $requestedProductId === 0) {
    grinco_quote_cart_set_flash('error', 'L’identifiant du produit demandé n’est pas valide.');
    header('Location: ' . grinco_url('/catalogue'));
    exit;
}

try {
    if ($requestedProductId > 0) {
        $requestedProduct = grinco_catalogue_public_product($requestedProductId);
        if (!$requestedProduct) {
            grinco_quote_cart_set_flash('error', 'Le produit demandé n’existe pas ou n’est pas disponible.');
            header('Location: ' . grinco_url('/catalogue'));
            exit;
        }
        $currentCart = grinco_quote_cart();
        if (!isset($currentCart[$requestedProductId])) {
            grinco_quote_cart_add($requestedProductId, 1);
        }
    }
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
function quote_option_selected($old, $field, $value, $defaultValue)
{
    $currentValue = isset($old[$field]) && is_scalar($old[$field]) ? (string) $old[$field] : (string) $defaultValue;
    return $currentValue === (string) $value ? ' selected' : '';
}
function quote_radio_checked($old, $field, $value)
{
    return isset($old[$field]) && is_scalar($old[$field]) && (string) $old[$field] === (string) $value ? ' checked' : '';
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
      <section class="quote-form-card quote-products-summary" aria-labelledby="quote-products-title"><div class="quote-form-card-header"><div class="quote-form-card-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div><div><span>Votre sélection</span><h2 id="quote-products-title">Produits demandés</h2></div></div><div class="table-responsive"><table><thead><tr><th>Référence</th><th>Produit</th><th>Modèle</th><th>Catégorie</th><th>Marque</th><th>Quantité</th></tr></thead><tbody><?php foreach ($quoteCartData['items'] as $item): ?><tr><td><?php echo htmlspecialchars($item['reference'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['modele'] === '' ? '—' : $item['modele'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['categorie_nom'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($item['marque_nom'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $item['quantite']; ?></td></tr><?php endforeach; ?></tbody></table></div><a href="<?php echo grinco_url_html('/panier-devis'); ?>" class="quote-edit-selection"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier la sélection</a></section>

      <form action="<?php echo grinco_url_html('/traitement-demande-devis'); ?>" method="POST" class="quote-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($quoteCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="form_started_at" value="<?php echo (int) $quoteFormStartedAt; ?>"><input type="hidden" name="submission_token" value="<?php echo htmlspecialchars($quoteSubmissionToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="quote-honeypot" aria-hidden="true"><label for="quote-website">Votre site</label><input id="quote-website" name="website" type="text" tabindex="-1" autocomplete="off"><label for="quote-company-url">URL entreprise</label><input id="quote-company-url" name="company_url" type="text" tabindex="-1" autocomplete="off"></div>
        <section class="quote-form-card" aria-labelledby="delivery-information-title"><div class="quote-form-card-header"><div class="quote-form-card-icon"><i class="bi bi-truck" aria-hidden="true"></i></div><div><span>Logistique</span><h2 id="delivery-information-title">Informations de livraison</h2></div></div><div class="row g-4">
          <div class="col-md-6"><label for="incoterm" class="quote-label">Type de prix souhaité (Incoterm)</label><select id="incoterm" name="incoterm" class="quote-control"><option value="conseil"<?php echo quote_option_selected($quoteOld, 'incoterm', 'conseil', 'conseil'); ?>>Je souhaite être conseillé</option><option value="exw"<?php echo quote_option_selected($quoteOld, 'incoterm', 'exw', 'conseil'); ?>>EXW – Départ usine</option><option value="fob"<?php echo quote_option_selected($quoteOld, 'incoterm', 'fob', 'conseil'); ?>>FOB – Chargé au port chinois</option><option value="cif"<?php echo quote_option_selected($quoteOld, 'incoterm', 'cif', 'conseil'); ?>>CIF – Transport et assurance jusqu’au port de destination</option></select></div>
          <div class="col-md-6"><label for="port_destination" class="quote-label">Port de destination</label><select id="port_destination" name="port_destination" class="quote-control" aria-controls="custom-port-field"><option value="dar_es_salaam"<?php echo quote_option_selected($quoteOld, 'port_destination', 'dar_es_salaam', 'dar_es_salaam'); ?>>Dar es Salaam (Tanzanie)</option><option value="tanga"<?php echo quote_option_selected($quoteOld, 'port_destination', 'tanga', 'dar_es_salaam'); ?>>Tanga (Tanzanie)</option><option value="durban"<?php echo quote_option_selected($quoteOld, 'port_destination', 'durban', 'dar_es_salaam'); ?>>Durban (Afrique du Sud)</option><option value="luanda"<?php echo quote_option_selected($quoteOld, 'port_destination', 'luanda', 'dar_es_salaam'); ?>>Luanda (Angola)</option><option value="mombasa"<?php echo quote_option_selected($quoteOld, 'port_destination', 'mombasa', 'dar_es_salaam'); ?>>Mombasa (Kenya)</option><option value="autre"<?php echo quote_option_selected($quoteOld, 'port_destination', 'autre', 'dar_es_salaam'); ?>>Autre</option></select></div>
          <div class="col-12 collapse<?php echo isset($quoteOld['port_destination']) && $quoteOld['port_destination'] === 'autre' ? ' show' : ''; ?>" id="custom-port-field"><label for="nom_port" class="quote-label">Nom du port</label><input type="text" id="nom_port" name="nom_port" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'nom_port'); ?>" maxlength="100" autocomplete="off"></div>
          <div class="col-md-6"><label for="ville_livraison" class="quote-label">Ville finale de livraison</label><input type="text" id="ville_livraison" name="ville_livraison" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'ville_livraison'); ?>" maxlength="100" placeholder="Exemple : Kolwezi" autocomplete="address-level2"></div>
          <fieldset class="col-md-6"><legend class="quote-label">Transport terrestre jusqu’à la destination finale</legend><div class="d-flex flex-wrap gap-3 pt-2"><div class="form-check"><input class="form-check-input" type="radio" name="transport_terrestre" id="transport_oui" value="oui"<?php echo quote_radio_checked($quoteOld, 'transport_terrestre', 'oui'); ?>><label class="form-check-label" for="transport_oui">Oui</label></div><div class="form-check"><input class="form-check-input" type="radio" name="transport_terrestre" id="transport_non" value="non"<?php echo quote_radio_checked($quoteOld, 'transport_terrestre', 'non'); ?>><label class="form-check-label" for="transport_non">Non</label></div><div class="form-check"><input class="form-check-input" type="radio" name="transport_terrestre" id="transport_definir" value="a_definir"<?php echo quote_radio_checked($quoteOld, 'transport_terrestre', 'a_definir'); ?>><label class="form-check-label" for="transport_definir">À définir</label></div></div></fieldset>
        </div></section>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
  var portSelect = document.getElementById('port_destination');
  var customPortField = document.getElementById('custom-port-field');
  if (!portSelect || !customPortField || typeof bootstrap === 'undefined') return;
  var customPortCollapse = bootstrap.Collapse.getOrCreateInstance(customPortField, { toggle: false });
  function updateCustomPortField() {
    var isOther = portSelect.value === 'autre';
    portSelect.setAttribute('aria-expanded', isOther ? 'true' : 'false');
    if (isOther) customPortCollapse.show(); else customPortCollapse.hide();
  }
  portSelect.addEventListener('change', updateCustomPortField);
  updateCustomPortField();
});
</script>
<?php include __DIR__ . '/includes/scripts.php'; ?>
