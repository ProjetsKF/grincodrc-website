<?php
require_once __DIR__ . '/includes/form-security.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$contactCsrfToken = grinco_csrf_token('contact');
$contactFormStartedAt = grinco_mark_form_opened('contact');
$contactTurnstileEnabled = grinco_turnstile_is_enabled();
$contactTurnstileSiteKey = grinco_turnstile_site_key();

$contactSuccess = isset($_SESSION['contact_success']) ? (string) $_SESSION['contact_success'] : '';
$contactError = isset($_SESSION['contact_error']) ? (string) $_SESSION['contact_error'] : '';
$contactValidationErrors = isset($_SESSION['contact_validation_errors']) && is_array($_SESSION['contact_validation_errors'])
    ? $_SESSION['contact_validation_errors']
    : array();
$contactFieldErrors = isset($_SESSION['contact_field_errors']) && is_array($_SESSION['contact_field_errors'])
    ? $_SESSION['contact_field_errors']
    : array();
$contactOld = isset($_SESSION['contact_old']) && is_array($_SESSION['contact_old'])
    ? $_SESSION['contact_old']
    : array();

unset(
    $_SESSION['contact_success'],
    $_SESSION['contact_error'],
    $_SESSION['contact_validation_errors'],
    $_SESSION['contact_field_errors'],
    $_SESSION['contact_old']
);

function contact_old_value($contactOld, $field)
{
    return isset($contactOld[$field]) && is_scalar($contactOld[$field])
        ? htmlspecialchars((string) $contactOld[$field], ENT_QUOTES, 'UTF-8')
        : '';
}

function contact_invalid_attribute($contactFieldErrors, $field)
{
    return !empty($contactFieldErrors[$field]) ? ' aria-invalid="true"' : '';
}

function contact_field_error($contactFieldErrors, $field)
{
    if (empty($contactFieldErrors[$field])) {
        return '';
    }

    return '<div class="form-security-field-error">'
        . htmlspecialchars((string) $contactFieldErrors[$field], ENT_QUOTES, 'UTF-8')
        . '</div>';
}

$pageTitle = 'Contact';
$pageDescription = 'Contactez GRINCO RDC pour vos besoins en véhicules, engins, pièces et services.';
$currentPage = 'contact';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Contact</h1>
      <nav class="breadcrumbs" aria-label="Fil d’Ariane">
        <ol>
          <li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li>
          <li class="current">Contact</li>
        </ol>
      </nav>
    </div>
  </div>

  <section id="contact" class="contact section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Contact</h2>
      <p>Notre équipe est à votre disposition pour répondre à vos questions et étudier vos besoins en véhicules, engins, pièces de rechange et services techniques.</p>
    </div>

    <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="contact-main-wrapper">
        <div class="map-wrapper">
          <iframe
            src="https://www.google.com/maps?q=Lubumbashi%2C%20R%C3%A9publique%20d%C3%A9mocratique%20du%20Congo&amp;output=embed"
            title="Carte de Lubumbashi, République démocratique du Congo"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
          </iframe>
        </div>

        <div class="contact-content">
          <div class="contact-cards-container">
            <article class="contact-card">
              <div class="icon-box">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
              </div>
              <div class="contact-text">
                <h4>Adresse</h4>
                <p class="contact-pending">Adresse officielle à confirmer — Lubumbashi, RDC</p>
              </div>
            </article>

            <article class="contact-card">
              <div class="icon-box">
                <i class="bi bi-envelope" aria-hidden="true"></i>
              </div>
              <div class="contact-text">
                <h4>E-mail</h4>
                <p class="contact-pending">Adresse e-mail officielle à confirmer</p>
              </div>
            </article>

            <article class="contact-card">
              <div class="icon-box">
                <i class="bi bi-telephone" aria-hidden="true"></i>
              </div>
              <div class="contact-text">
                <h4>Téléphone</h4>
                <p class="contact-pending">Numéro officiel à confirmer</p>
              </div>
            </article>

            <article class="contact-card">
              <div class="icon-box">
                <i class="bi bi-clock" aria-hidden="true"></i>
              </div>
              <div class="contact-text">
                <h4>Heures d’ouverture</h4>
                <p>Lundi – Vendredi : 08 h 00 – 17 h 00<br>Samedi : sur rendez-vous</p>
              </div>
            </article>
          </div>

          <div class="contact-form-container">
            <h3>Contactez-nous</h3>
            <p>Remplissez ce formulaire pour nous transmettre votre demande. Notre équipe vous répondra dans les meilleurs délais.</p>

            <div id="contact-form-feedback" aria-live="polite" aria-atomic="true">
              <?php if ($contactSuccess !== ''): ?>
                <div class="alert alert-success contact-form-alert" role="status">
                  <i class="bi bi-check-circle" aria-hidden="true"></i>
                  <div>
                    <strong>Message envoyé</strong>
                    <p><?php echo htmlspecialchars($contactSuccess, ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                </div>
              <?php elseif ($contactError !== ''): ?>
                <div class="alert alert-danger contact-form-alert" role="alert">
                  <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                  <div>
                    <strong>Envoi impossible</strong>
                    <p><?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (!empty($contactValidationErrors)): ?>
                      <ul>
                        <?php foreach ($contactValidationErrors as $contactValidationError): ?>
                          <li><?php echo htmlspecialchars($contactValidationError, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <form action="<?php echo grinco_url_html('/traitement-contact'); ?>" method="POST" accept-charset="UTF-8" class="contact-visual-form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($contactCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="form_started_at" value="<?php echo (int) $contactFormStartedAt; ?>">

              <div class="contact-honeypot" aria-hidden="true">
                <label for="contact-website">Votre site internet</label>
                <input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off">
                <label for="contact-company-url">Adresse du site de votre entreprise</label>
                <input type="text" id="contact-company-url" name="company_url" tabindex="-1" autocomplete="off">
              </div>

              <div class="row gy-4">
                <div class="col-md-6">
                  <label for="contact-name" class="form-label">Nom complet <span aria-hidden="true">*</span></label>
                  <input type="text" id="contact-name" name="full_name" class="form-control" value="<?php echo contact_old_value($contactOld, 'full_name'); ?>" maxlength="100" autocomplete="name" required<?php echo contact_invalid_attribute($contactFieldErrors, 'full_name'); ?>>
                  <?php echo contact_field_error($contactFieldErrors, 'full_name'); ?>
                </div>

                <div class="col-md-6">
                  <label for="contact-email" class="form-label">Adresse e-mail <span aria-hidden="true">*</span></label>
                  <input type="email" id="contact-email" name="email" class="form-control" value="<?php echo contact_old_value($contactOld, 'email'); ?>" maxlength="190" autocomplete="email" required<?php echo contact_invalid_attribute($contactFieldErrors, 'email'); ?>>
                  <?php echo contact_field_error($contactFieldErrors, 'email'); ?>
                </div>

                <div class="col-md-6">
                  <label for="contact-phone" class="form-label">Téléphone</label>
                  <input type="tel" id="contact-phone" name="phone" class="form-control" value="<?php echo contact_old_value($contactOld, 'phone'); ?>" maxlength="25" autocomplete="tel" inputmode="tel"<?php echo contact_invalid_attribute($contactFieldErrors, 'phone'); ?>>
                  <?php echo contact_field_error($contactFieldErrors, 'phone'); ?>
                </div>

                <div class="col-md-6">
                  <label for="contact-subject" class="form-label">Objet <span aria-hidden="true">*</span></label>
                  <input type="text" id="contact-subject" name="subject" class="form-control" value="<?php echo contact_old_value($contactOld, 'subject'); ?>" maxlength="150" required<?php echo contact_invalid_attribute($contactFieldErrors, 'subject'); ?>>
                  <?php echo contact_field_error($contactFieldErrors, 'subject'); ?>
                </div>

                <div class="col-12">
                  <label for="contact-message" class="form-label">Message <span aria-hidden="true">*</span></label>
                  <textarea id="contact-message" name="message" class="form-control" rows="6" maxlength="3000" required<?php echo contact_invalid_attribute($contactFieldErrors, 'message'); ?>><?php echo contact_old_value($contactOld, 'message'); ?></textarea>
                  <?php echo contact_field_error($contactFieldErrors, 'message'); ?>
                </div>
              </div>

              <?php if ($contactTurnstileEnabled): ?>
                <div class="form-security-turnstile">
                  <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($contactTurnstileSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
              <?php endif; ?>

              <div class="form-submit">
                <button type="submit">Envoyer le message</button>

                <div class="social-links" aria-label="Réseaux sociaux de GRINCO RDC">
                  <!-- Liens temporaires : remplacer "#" uniquement après validation des comptes officiels. -->
                  <a href="#" aria-label="Facebook — lien temporaire"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                  <a href="#" aria-label="Instagram — lien temporaire"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                  <a href="#" aria-label="LinkedIn — lien temporaire"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
                  <a href="#" aria-label="WhatsApp — lien temporaire"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php if ($contactTurnstileEnabled): ?>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
