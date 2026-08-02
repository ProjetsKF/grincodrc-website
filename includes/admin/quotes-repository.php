<?php

require_once dirname(__DIR__) . '/database.php';
require_once dirname(__DIR__) . '/catalogue-files.php';

function grinco_quotes_search_clause($search, &$parameters)
{
    if ($search === '') {
        return '';
    }
    $pattern = '%' . $search . '%';
    $parameters = array(
        ':search_name' => $pattern,
        ':search_company' => $pattern,
        ':search_phone' => $pattern,
        ':search_email' => $pattern,
        ':search_product_reference' => $pattern,
        ':search_product_name' => $pattern
    );
    return ' WHERE d.nom LIKE :search_name OR COALESCE(d.entreprise, \'\') LIKE :search_company '
        . 'OR d.telephone LIKE :search_phone OR COALESCE(d.email, \'\') LIKE :search_email '
        . 'OR EXISTS (SELECT 1 FROM demande_devis_details sd '
        . 'INNER JOIN produits sp ON sp.id = sd.produit_id '
        . 'WHERE sd.demande_id = d.id AND (sp.reference LIKE :search_product_reference OR sp.nom LIKE :search_product_name))';
}

function grinco_quotes_fetch_page($search, $requestedPage, $perPage)
{
    $connection = grinco_database();
    $parameters = array();
    $where = grinco_quotes_search_clause($search, $parameters);
    $count = $connection->prepare('SELECT COUNT(*) FROM demandes_devis d' . $where);
    $count->execute($parameters);
    $total = (int) $count->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $requestedPage), $totalPages);
    $offset = ($page - 1) * $perPage;

    $statement = $connection->prepare(
        'SELECT d.id, d.nom, d.entreprise, d.telephone, d.email, d.date_demande, '
        . 'COUNT(dd.id) AS nombre_produits '
        . 'FROM demandes_devis d LEFT JOIN demande_devis_details dd ON dd.demande_id = d.id '
        . $where . ' GROUP BY d.id, d.nom, d.entreprise, d.telephone, d.email, d.date_demande '
        . 'ORDER BY d.date_demande DESC, d.id DESC LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['entreprise'] = $row['entreprise'] === null ? '' : (string) $row['entreprise'];
        $row['email'] = $row['email'] === null ? '' : (string) $row['email'];
        $row['nombre_produits'] = (int) $row['nombre_produits'];
        $row['date_formatted'] = date('d/m/Y H:i', strtotime($row['date_demande']));
    }
    unset($row);
    return array('rows' => $rows, 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => $totalPages, 'offset' => $offset);
}

function grinco_quotes_find_request($requestId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, nom, entreprise, telephone, email, message, date_demande '
        . 'FROM demandes_devis WHERE id = :id LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $requestId));
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }
    foreach (array('entreprise', 'email', 'message') as $field) {
        $row[$field] = $row[$field] === null ? '' : (string) $row[$field];
    }
    $row['id'] = (int) $row['id'];
    $row['date_formatted'] = date('d/m/Y H:i', strtotime($row['date_demande']));
    return $row;
}

function grinco_quotes_request_products($requestId)
{
    $statement = grinco_database()->prepare(
        'SELECT dd.id, dd.quantite, p.id AS produit_id, p.reference, p.nom, p.modele, p.prix, '
        . 'c.nom AS categorie_nom, m.nom AS marque_nom, '
        . '(SELECT pi.image FROM produit_images pi WHERE pi.produit_id = p.id '
        . 'AND pi.image_principale = 1 ORDER BY pi.id ASC LIMIT 1) AS image_principale '
        . 'FROM demande_devis_details dd INNER JOIN produits p ON p.id = dd.produit_id '
        . 'INNER JOIN categories c ON c.id = p.categorie_id INNER JOIN marques m ON m.id = p.marque_id '
        . 'WHERE dd.demande_id = :request_id ORDER BY dd.id ASC'
    );
    $statement->execute(array(':request_id' => (int) $requestId));
    $rateStatement = grinco_database()->prepare(
        "SELECT taux FROM taux_change WHERE devise_source = 'USD' AND devise_destination = 'CNY' ORDER BY id DESC LIMIT 1"
    );
    $rateStatement->execute();
    $rate = $rateStatement->fetchColumn();
    $rate = $rate === false ? null : (float) $rate;

    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $row['quantite'] = (int) $row['quantite'];
        $row['modele'] = $row['modele'] === null ? '' : (string) $row['modele'];
        $row['image_principale'] = $row['image_principale'] === null ? '' : (string) $row['image_principale'];
        $row['image_url'] = $row['image_principale'] === '' ? '' : grinco_catalogue_file_url($row['image_principale'], 'image');
        $row['prix_usd_formatted'] = number_format((float) $row['prix'], 2, ',', ' ') . ' USD';
        $row['prix_cny_formatted'] = $rate === null ? 'Non disponible' : number_format((float) $row['prix'] * $rate, 2, ',', ' ') . ' CNY';
    }
    unset($row);
    return array('rows' => $rows, 'rate' => $rate);
}
