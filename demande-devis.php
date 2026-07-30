<?php
require_once __DIR__ . '/includes/form-security.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$quoteCsrfToken = grinco_csrf_token('quote');
$quoteFormStartedAt = grinco_mark_form_opened('quote');
$quoteTurnstileEnabled = grinco_turnstile_is_enabled();
$quoteTurnstileSiteKey = grinco_turnstile_site_key();

$quoteSuccess = isset($_SESSION['quote_success']) ? (string) $_SESSION['quote_success'] : '';
$quoteError = isset($_SESSION['quote_error']) ? (string) $_SESSION['quote_error'] : '';
$quoteValidationErrors = isset($_SESSION['quote_validation_errors']) && is_array($_SESSION['quote_validation_errors'])
    ? $_SESSION['quote_validation_errors']
    : array();
$quoteFieldErrors = isset($_SESSION['quote_field_errors']) && is_array($_SESSION['quote_field_errors'])
    ? $_SESSION['quote_field_errors']
    : array();
$quoteOld = isset($_SESSION['quote_old']) && is_array($_SESSION['quote_old']) ? $_SESSION['quote_old'] : array();
unset(
    $_SESSION['quote_success'],
    $_SESSION['quote_error'],
    $_SESSION['quote_validation_errors'],
    $_SESSION['quote_field_errors'],
    $_SESSION['quote_old']
);

function quote_old_value($quoteOld, $field)
{
    return isset($quoteOld[$field]) && is_scalar($quoteOld[$field])
        ? htmlspecialchars((string) $quoteOld[$field], ENT_QUOTES, 'UTF-8')
        : '';
}

function quote_is_selected($quoteOld, $field, $value)
{
    return isset($quoteOld[$field]) && (string) $quoteOld[$field] === (string) $value ? ' selected' : '';
}

function quote_invalid_attribute($quoteFieldErrors, $field)
{
    return !empty($quoteFieldErrors[$field]) ? ' aria-invalid="true"' : '';
}

function quote_field_error($quoteFieldErrors, $field)
{
    if (empty($quoteFieldErrors[$field])) {
        return '';
    }

    return '<div class="form-security-field-error">'
        . htmlspecialchars((string) $quoteFieldErrors[$field], ENT_QUOTES, 'UTF-8')
        . '</div>';
}

$pageTitle = 'Demande de devis';
$pageDescription = 'Décrivez votre besoin pour recevoir une proposition technique et financière adaptée de GRINCO RDC.';
$currentPage = 'demande-devis';
$bodyClass = 'quote-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<main class="main">
  <div class="page-title light-background">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Demande de devis</h1>
      <nav class="breadcrumbs" aria-label="Fil d’Ariane">
        <ol>
          <li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li>
          <li class="current" aria-current="page">Demande de devis</li>
        </ol>
      </nav>
    </div>
  </div>

  <section class="quote-intro section light-background">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-8" data-aos="fade-right">
          <span class="quote-eyebrow">Étude personnalisée</span>
          <h2>Parlez-nous de votre besoin</h2>
          <p>Renseignez les informations disponibles sur votre projet. L’équipe GRINCO RDC analysera votre demande afin de préparer une proposition technique et financière adaptée.</p>
        </div>
        <div class="col-lg-4" data-aos="fade-left">
          <div class="quote-intro-note">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <div>
              <strong>Demande confidentielle</strong>
              <span>Vos informations sont utilisées uniquement pour traiter votre demande.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="quote-form-section section">
    <div class="container">
      <div id="quote-form-feedback" aria-live="polite">
      <?php if ($quoteSuccess !== ''): ?>
        <div class="alert alert-success quote-alert quote-alert-success" role="status">
          <i class="bi bi-check-circle" aria-hidden="true"></i>
          <div>
            <strong>Demande envoyée</strong>
            <p><?php echo htmlspecialchars($quoteSuccess, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>
      <?php elseif ($quoteError !== ''): ?>
        <div class="alert alert-danger quote-alert quote-alert-error" role="alert">
          <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
          <div>
            <strong>Envoi impossible</strong>
            <p><?php echo htmlspecialchars($quoteError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (!empty($quoteValidationErrors)): ?>
              <ul>
                <?php foreach ($quoteValidationErrors as $quoteValidationError): ?>
                  <li><?php echo htmlspecialchars($quoteValidationError, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      </div>

      <div class="row gy-4">
        <div class="col-lg-8">
          <form action="<?php echo grinco_url_html('/traitement-demande-devis'); ?>" method="POST" accept-charset="UTF-8" class="quote-form" data-aos="fade-up">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($quoteCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="form_started_at" value="<?php echo (int) $quoteFormStartedAt; ?>">

            <div class="quote-honeypot" aria-hidden="true">
              <label for="quote-website">Votre site internet</label>
              <input type="text" id="quote-website" name="website" tabindex="-1" autocomplete="off">
              <label for="quote-company-url">Adresse du site de votre entreprise</label>
              <input type="text" id="quote-company-url" name="company_url" tabindex="-1" autocomplete="off">
            </div>

            <section class="quote-form-card" aria-labelledby="client-information-title">
              <div class="quote-form-card-header">
                <div class="quote-form-card-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></div>
                <div>
                  <span>Étape 1</span>
                  <h3 id="client-information-title">Informations du client</h3>
                </div>
              </div>

              <div class="row g-4">
                <div class="col-md-6">
                  <label for="full_name" class="quote-label">Nom complet <span aria-hidden="true">*</span></label>
                  <input type="text" id="full_name" name="full_name" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'full_name'); ?>" maxlength="100" autocomplete="name" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'full_name'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'full_name'); ?>
                </div>
                <div class="col-md-6">
                  <label for="company" class="quote-label">Entreprise</label>
                  <input type="text" id="company" name="company" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'company'); ?>" maxlength="150" autocomplete="organization"<?php echo quote_invalid_attribute($quoteFieldErrors, 'company'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'company'); ?>
                </div>
                <div class="col-md-6">
                  <label for="email" class="quote-label">Adresse e-mail <span aria-hidden="true">*</span></label>
                  <input type="email" id="email" name="email" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'email'); ?>" maxlength="190" autocomplete="email" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'email'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'email'); ?>
                </div>
                <div class="col-md-6">
                  <label for="phone" class="quote-label">Téléphone <span aria-hidden="true">*</span></label>
                  <input type="tel" id="phone" name="phone" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'phone'); ?>" maxlength="25" autocomplete="tel" inputmode="tel" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'phone'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'phone'); ?>
                </div>
                <div class="col-md-6">
                  <label for="whatsapp" class="quote-label">Numéro WhatsApp</label>
                  <input type="tel" id="whatsapp" name="whatsapp" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'whatsapp'); ?>" maxlength="25" autocomplete="tel" inputmode="tel" placeholder="+243…"<?php echo quote_invalid_attribute($quoteFieldErrors, 'whatsapp'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'whatsapp'); ?>
                </div>
                <div class="col-md-3">
                  <label for="city" class="quote-label">Ville</label>
                  <input type="text" id="city" name="city" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'city'); ?>" maxlength="100" autocomplete="address-level2"<?php echo quote_invalid_attribute($quoteFieldErrors, 'city'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'city'); ?>
                </div>
                <div class="col-md-3">
                  <label for="province" class="quote-label">Province</label>
                  <input type="text" id="province" name="province" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'province'); ?>" maxlength="100" autocomplete="address-level1"<?php echo quote_invalid_attribute($quoteFieldErrors, 'province'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'province'); ?>
                </div>
              </div>
            </section>

            <section class="quote-form-card" aria-labelledby="request-information-title">
              <div class="quote-form-card-header">
                <div class="quote-form-card-icon"><i class="bi bi-truck" aria-hidden="true"></i></div>
                <div>
                  <span>Étape 2</span>
                  <h3 id="request-information-title">Informations sur le besoin</h3>
                </div>
              </div>

              <div class="row g-4">
                <div class="col-md-6">
                  <label for="category" class="quote-label">Catégorie d’équipement <span aria-hidden="true">*</span></label>
                  <select id="category" name="category" class="quote-control" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'category'); ?>>
                    <option value="">Sélectionner une catégorie</option>
                    <option value="Camion"<?php echo quote_is_selected($quoteOld, 'category', 'Camion'); ?>>Camion</option>
                    <option value="Semi-remorque"<?php echo quote_is_selected($quoteOld, 'category', 'Semi-remorque'); ?>>Semi-remorque</option>
                    <option value="Engin lourd"<?php echo quote_is_selected($quoteOld, 'category', 'Engin lourd'); ?>>Engin lourd</option>
                    <option value="Véhicule particulier"<?php echo quote_is_selected($quoteOld, 'category', 'Véhicule particulier'); ?>>Véhicule particulier</option>
                    <option value="Pièces de rechange"<?php echo quote_is_selected($quoteOld, 'category', 'Pièces de rechange'); ?>>Pièces de rechange</option>
                    <option value="Service d’ingénierie"<?php echo quote_is_selected($quoteOld, 'category', 'Service d’ingénierie'); ?>>Service d’ingénierie</option>
                    <option value="Maintenance industrielle"<?php echo quote_is_selected($quoteOld, 'category', 'Maintenance industrielle'); ?>>Maintenance industrielle</option>
                    <option value="Autre"<?php echo quote_is_selected($quoteOld, 'category', 'Autre'); ?>>Autre</option>
                  </select>
                  <?php echo quote_field_error($quoteFieldErrors, 'category'); ?>
                </div>
                <div class="col-md-3">
                  <label for="brand" class="quote-label">Marque souhaitée</label>
                  <input type="text" id="brand" name="brand" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'brand'); ?>" maxlength="100"<?php echo quote_invalid_attribute($quoteFieldErrors, 'brand'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'brand'); ?>
                </div>
                <div class="col-md-3">
                  <label for="model" class="quote-label">Modèle souhaité</label>
                  <input type="text" id="model" name="model" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'model'); ?>" maxlength="100"<?php echo quote_invalid_attribute($quoteFieldErrors, 'model'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'model'); ?>
                </div>
                <div class="col-md-4">
                  <label for="quantity" class="quote-label">Quantité <span aria-hidden="true">*</span></label>
                  <input type="number" id="quantity" name="quantity" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'quantity'); ?>" min="1" max="1000" step="1" inputmode="numeric" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'quantity'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'quantity'); ?>
                </div>
                <div class="col-md-4">
                  <label for="delivery_location" class="quote-label">Lieu de livraison <span aria-hidden="true">*</span></label>
                  <input type="text" id="delivery_location" name="delivery_location" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'delivery_location'); ?>" maxlength="200" required<?php echo quote_invalid_attribute($quoteFieldErrors, 'delivery_location'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'delivery_location'); ?>
                </div>
                <div class="col-md-4">
                  <label for="desired_deadline" class="quote-label">Délai souhaité</label>
                  <input type="text" id="desired_deadline" name="desired_deadline" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'desired_deadline'); ?>" maxlength="100" placeholder="Ex. : sous 3 mois"<?php echo quote_invalid_attribute($quoteFieldErrors, 'desired_deadline'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'desired_deadline'); ?>
                </div>
                <div class="col-12">
                  <label for="intended_use" class="quote-label">Utilisation prévue</label>
                  <textarea id="intended_use" name="intended_use" class="quote-control" rows="4" maxlength="1000" placeholder="Décrivez le contexte d’utilisation, le terrain, les charges ou les opérations prévues."<?php echo quote_invalid_attribute($quoteFieldErrors, 'intended_use'); ?>><?php echo quote_old_value($quoteOld, 'intended_use'); ?></textarea>
                  <?php echo quote_field_error($quoteFieldErrors, 'intended_use'); ?>
                </div>
                <div class="col-12">
                  <label for="technical_requirements" class="quote-label">Caractéristiques techniques</label>
                  <textarea id="technical_requirements" name="technical_requirements" class="quote-control" rows="5" maxlength="3000" placeholder="Capacité, puissance, dimensions, configuration, options ou normes souhaitées."<?php echo quote_invalid_attribute($quoteFieldErrors, 'technical_requirements'); ?>><?php echo quote_old_value($quoteOld, 'technical_requirements'); ?></textarea>
                  <?php echo quote_field_error($quoteFieldErrors, 'technical_requirements'); ?>
                </div>
                <div class="col-md-6">
                  <label for="indicative_budget" class="quote-label">Budget indicatif</label>
                  <input type="text" id="indicative_budget" name="indicative_budget" class="quote-control" value="<?php echo quote_old_value($quoteOld, 'indicative_budget'); ?>" maxlength="100" placeholder="Montant et devise"<?php echo quote_invalid_attribute($quoteFieldErrors, 'indicative_budget'); ?>>
                  <?php echo quote_field_error($quoteFieldErrors, 'indicative_budget'); ?>
                </div>
                <div class="col-12">
                  <label for="additional_message" class="quote-label">Message complémentaire</label>
                  <textarea id="additional_message" name="additional_message" class="quote-control" rows="5" maxlength="3000"<?php echo quote_invalid_attribute($quoteFieldErrors, 'additional_message'); ?>><?php echo quote_old_value($quoteOld, 'additional_message'); ?></textarea>
                  <?php echo quote_field_error($quoteFieldErrors, 'additional_message'); ?>
                </div>
              </div>
            </section>

            <div class="quote-consent">
              <input type="checkbox" id="consent" name="consent" value="1"<?php echo !empty($quoteOld['consent']) ? ' checked' : ''; ?> required<?php echo quote_invalid_attribute($quoteFieldErrors, 'consent'); ?>>
              <label for="consent">J’accepte que mes informations soient utilisées par GRINCO RDC pour traiter ma demande de devis. <span aria-hidden="true">*</span></label>
            </div>
            <?php echo quote_field_error($quoteFieldErrors, 'consent'); ?>

            <?php if ($quoteTurnstileEnabled): ?>
              <div class="form-security-turnstile">
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($quoteTurnstileSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
              </div>
            <?php endif; ?>

            <div class="quote-submit-row">
              <p><span aria-hidden="true">*</span> Champs obligatoires</p>
              <button type="submit" class="quote-submit-btn">
                <span>Envoyer ma demande</span>
                <i class="bi bi-send" aria-hidden="true"></i>
              </button>
            </div>
          </form>
        </div>

        <aside class="col-lg-4" data-aos="fade-left" data-aos-delay="100">
          <div class="quote-sidebar">
            <div class="quote-sidebar-card">
              <span class="quote-sidebar-icon"><i class="bi bi-list-check" aria-hidden="true"></i></span>
              <h3>Pour une étude précise</h3>
              <p>Plus votre demande est détaillée, plus notre équipe pourra identifier une solution adaptée.</p>
              <ul>
                <li>Usage et environnement de travail</li>
                <li>Capacité, puissance ou dimensions</li>
                <li>Quantité et lieu de livraison</li>
                <li>Délai et budget indicatif</li>
              </ul>
            </div>

            <div class="quote-sidebar-card quote-sidebar-contact">
              <span class="quote-sidebar-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
              <h3>Besoin d’assistance&nbsp;?</h3>
              <p>Vous pouvez aussi contacter directement l’équipe GRINCO RDC.</p>
              <a href="mailto:info@grincodrc.com">info@grincodrc.com</a>
              <a href="<?php echo grinco_url_html('/contact'); ?>" class="quote-contact-link">Voir nos coordonnées <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>

<?php if ($quoteTurnstileEnabled): ?>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
