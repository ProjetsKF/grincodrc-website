<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

$adminPageTitle = 'Tableau de bord';
$adminPageDescription = 'Vue d’ensemble du catalogue et des demandes de devis GRINCO RDC.';
$adminCurrentPage = 'dashboard';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$statistics = array(
    'products' => 0,
    'categories' => 0,
    'brands' => 0,
    'quotes' => 0
);
$statisticsError = '';

try {
    $statisticsStatement = grinco_database()->prepare(
        'SELECT '
        . '(SELECT COUNT(*) FROM produits) AS products, '
        . '(SELECT COUNT(*) FROM categories) AS categories, '
        . '(SELECT COUNT(*) FROM marques) AS brands, '
        . '(SELECT COUNT(*) FROM demandes_devis) AS quotes'
    );
    $statisticsStatement->execute();
    $databaseStatistics = $statisticsStatement->fetch();

    if ($databaseStatistics) {
        foreach ($statistics as $key => $value) {
            $statistics[$key] = isset($databaseStatistics[$key])
                ? max(0, (int) $databaseStatistics[$key])
                : 0;
        }
    }
} catch (PDOException $exception) {
    error_log('[GRINCO admin dashboard] Unable to load statistics.');
    $statisticsError = 'Les statistiques ne peuvent pas être actualisées pour le moment.';
}

function admin_statistic_value($value)
{
    return number_format((int) $value, 0, ',', ' ');
}

include dirname(__DIR__) . '/includes/admin/head.php';
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>

  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>

    <main class="admin-content" id="admin-main-content">
      <div class="admin-page-heading">
        <div>
          <span class="admin-eyebrow">Vue d’ensemble</span>
          <h1>Tableau de bord</h1>
          <p>Bienvenue, <?php echo grinco_admin_escape($_SESSION['grinco_admin_name']); ?>. Retrouvez ici les principaux indicateurs du catalogue GRINCO RDC.</p>
        </div>
      </div>

      <?php if ($statisticsError !== ''): ?>
        <div class="admin-data-alert" role="alert">
          <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
          <span><?php echo grinco_admin_escape($statisticsError); ?></span>
        </div>
      <?php endif; ?>

      <section class="admin-stat-grid" aria-label="Statistiques du catalogue">
        <article class="admin-stat-card">
          <div class="admin-stat-copy">
            <span class="admin-stat-label">Produits</span>
            <strong class="admin-stat-value"><?php echo grinco_admin_escape(admin_statistic_value($statistics['products'])); ?></strong>
          </div>
          <span class="admin-stat-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
        </article>

        <article class="admin-stat-card is-red">
          <div class="admin-stat-copy">
            <span class="admin-stat-label">Catégories</span>
            <strong class="admin-stat-value"><?php echo grinco_admin_escape(admin_statistic_value($statistics['categories'])); ?></strong>
          </div>
          <span class="admin-stat-icon" aria-hidden="true"><i class="bi bi-tags"></i></span>
        </article>

        <article class="admin-stat-card is-dark">
          <div class="admin-stat-copy">
            <span class="admin-stat-label">Marques</span>
            <strong class="admin-stat-value"><?php echo grinco_admin_escape(admin_statistic_value($statistics['brands'])); ?></strong>
          </div>
          <span class="admin-stat-icon" aria-hidden="true"><i class="bi bi-bookmark-star"></i></span>
        </article>

        <article class="admin-stat-card is-gold">
          <div class="admin-stat-copy">
            <span class="admin-stat-label">Demandes de devis</span>
            <strong class="admin-stat-value"><?php echo grinco_admin_escape(admin_statistic_value($statistics['quotes'])); ?></strong>
          </div>
          <span class="admin-stat-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
        </article>
      </section>

      <section class="admin-dashboard-grid" aria-label="Aperçu de l’activité">
        <article class="admin-panel">
          <div class="admin-panel-header">
            <div>
              <h2>Organisation du catalogue</h2>
              <p>Répartition actuelle des données principales.</p>
            </div>
            <?php if ($statisticsError === ''): ?>
              <span class="admin-panel-badge"><i class="bi bi-database-check" aria-hidden="true"></i> Données actualisées</span>
            <?php endif; ?>
          </div>

          <dl class="admin-overview-list">
            <div class="admin-overview-item">
              <dt>Produits enregistrés</dt>
              <dd><?php echo grinco_admin_escape(admin_statistic_value($statistics['products'])); ?></dd>
            </div>
            <div class="admin-overview-item">
              <dt>Catégories disponibles</dt>
              <dd><?php echo grinco_admin_escape(admin_statistic_value($statistics['categories'])); ?></dd>
            </div>
            <div class="admin-overview-item">
              <dt>Marques référencées</dt>
              <dd><?php echo grinco_admin_escape(admin_statistic_value($statistics['brands'])); ?></dd>
            </div>
          </dl>
        </article>

        <article class="admin-panel">
          <div class="admin-panel-header">
            <div>
              <h2>Suivi des devis</h2>
              <p>Demandes reçues depuis le site public.</p>
            </div>
          </div>

          <div class="admin-empty-state">
            <span class="admin-empty-state-icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></span>
            <strong><?php echo grinco_admin_escape(admin_statistic_value($statistics['quotes'])); ?> demande<?php echo $statistics['quotes'] === 1 ? '' : 's'; ?></strong>
            <p>Le module de gestion des demandes sera activé lors de sa phase de développement.</p>
          </div>
        </article>
      </section>
    </main>

    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
