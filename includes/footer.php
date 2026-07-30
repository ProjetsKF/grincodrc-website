<?php require_once dirname(__DIR__) . '/config/app.php'; ?>
<footer id="footer" class="footer grinco-footer position-relative">
  <div class="container footer-top">
    <div class="row gy-4">
      <div class="col-lg-4 col-md-6 footer-about">
        <a href="<?php echo grinco_url_html('/'); ?>" class="logo d-flex align-items-center">
          <img src="assets/img/logo-bas.png" alt="GRINCO RDC" class="logo-footer">
        </a>
        <div class="footer-contact pt-3">
          <p>République démocratique du Congo</p>
          <p>Coordonnées complètes à confirmer</p>
          <p class="mt-3"><strong>Téléphone :</strong> <span>+8613241687801</span></p>
          <p><strong>Email :</strong> <span>info@grincodrc.com</span></p>
        </div>
      </div>

      <div class="col-lg-2 col-md-3 footer-links">
        <h4>Navigation</h4>
        <ul>
          <li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li>
          <li><a href="<?php echo grinco_url_html('/a-propos'); ?>">À propos</a></li>
          <li><a href="<?php echo grinco_url_html('/catalogue'); ?>">Catalogue</a></li>
          <li><a href="<?php echo grinco_url_html('/services'); ?>">Services</a></li>
          <li><a href="<?php echo grinco_url_html('/contact'); ?>">Contact</a></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-3 footer-links">
        <h4>Nos offres</h4>
        <ul>
          <li><a href="<?php echo grinco_url_html('/camions'); ?>">Camions</a></li>
          <li><a href="<?php echo grinco_url_html('/semi-remorques'); ?>">Semi-remorques</a></li>
          <li><a href="<?php echo grinco_url_html('/engins-lourds'); ?>">Engins lourds</a></li>
          <li><a href="<?php echo grinco_url_html('/vehicules-particuliers'); ?>">Véhicules particuliers</a></li>
          <li><a href="<?php echo grinco_url_html('/pieces-de-rechange'); ?>">Pièces de rechange</a></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-6 footer-links">
        <h4>Informations</h4>
        <ul>
          <li><a href="<?php echo grinco_url_html('/partenaires'); ?>">Partenaires</a></li>
          <li><a href="<?php echo grinco_url_html('/comment-commander'); ?>">Comment commander</a></li>
          <li><a href="<?php echo grinco_url_html('/actualites'); ?>">Actualités</a></li>
          <li><a href="<?php echo grinco_url_html('/galerie'); ?>">Galerie</a></li>
          <li><a href="<?php echo grinco_url_html('/demande-devis'); ?>">Demande de devis</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container copyright text-center mt-4">
    <p>&copy; <span><?php echo date('Y'); ?></span> <strong class="px-1 sitename">GRINCO RDC</strong> <span>Tous droits réservés</span></p>
    <div class="credits">
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
    <div class="footer-administration">
      <a href="<?php echo grinco_url_html('/connexion'); ?>" class="footer-admin-link">Administration</a>
    </div>
  </div>
</footer>

<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center" aria-label="Revenir en haut">
  <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
</a>
