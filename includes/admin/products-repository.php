<?php

require_once dirname(__DIR__) . '/database.php';
require_once dirname(__DIR__) . '/catalogue-files.php';
require_once __DIR__ . '/exchange-rates.php';

function grinco_products_has_creation_date()
{
    static $hasCreationDate = null;

    if ($hasCreationDate === null) {
        $statement = grinco_database()->prepare("SHOW COLUMNS FROM produits LIKE 'date_creation'");
        $statement->execute();
        $hasCreationDate = (bool) $statement->fetch();
    }

    return $hasCreationDate;
}

function grinco_products_format_price($price)
{
    return number_format((float) $price, 2, ',', ' ') . ' USD';
}

function grinco_products_fetch_page($search, $requestedPage, $perPage)
{
    $connection = grinco_database();
    $where = '';
    $parameters = array();

    if ($search !== '') {
        $where = ' WHERE p.reference LIKE :search_reference'
            . ' OR p.nom LIKE :search_name'
            . ' OR COALESCE(p.modele, \'\') LIKE :search_model'
            . ' OR c.nom LIKE :search_category'
            . ' OR m.nom LIKE :search_brand';
        $searchPattern = '%' . $search . '%';
        $parameters = array(
            ':search_reference' => $searchPattern,
            ':search_name' => $searchPattern,
            ':search_model' => $searchPattern,
            ':search_category' => $searchPattern,
            ':search_brand' => $searchPattern
        );
    }

    $from = ' FROM produits p'
        . ' INNER JOIN categories c ON c.id = p.categorie_id'
        . ' INNER JOIN marques m ON m.id = p.marque_id'
        . ' INNER JOIN utilisateurs_admin ua ON ua.id = p.administrateur_id';

    $countStatement = $connection->prepare('SELECT COUNT(*)' . $from . $where);
    $countStatement->execute($parameters);
    $total = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $requestedPage), $totalPages);
    $offset = ($page - 1) * $perPage;
    $creationColumn = grinco_products_has_creation_date() ? 'p.date_creation' : 'NULL';

    $statement = $connection->prepare(
        'SELECT p.id, p.categorie_id, p.marque_id, p.reference, p.nom, p.modele, p.prix, p.description,'
        . ' (SELECT pi.image FROM produit_images pi WHERE pi.produit_id = p.id '
        . ' AND pi.image_principale = 1 ORDER BY pi.id ASC LIMIT 1) AS image_principale,'
        . ' c.nom AS categorie_nom, m.nom AS marque_nom, ua.nom AS administrateur_nom,'
        . ' ' . $creationColumn . ' AS date_creation'
        . $from
        . $where
        . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset'
    );

    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $statement->execute();

    $rows = $statement->fetchAll();
    $usdCnyRate = grinco_admin_usd_cny_rate();
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['categorie_id'] = (int) $row['categorie_id'];
        $row['marque_id'] = (int) $row['marque_id'];
        $row['modele'] = $row['modele'] === null ? '' : (string) $row['modele'];
        $row['description'] = $row['description'] === null ? '' : (string) $row['description'];
        $row['image_principale'] = $row['image_principale'] === null ? '' : (string) $row['image_principale'];
        $row['image_url'] = $row['image_principale'] === ''
            ? ''
            : grinco_catalogue_file_url($row['image_principale'], 'image');
        $row['prix'] = number_format((float) $row['prix'], 2, '.', '');
        $row['prix_formatted'] = grinco_products_format_price($row['prix']);
        $convertedPrice = grinco_admin_convert_usd_to_cny($row['prix'], $usdCnyRate);
        $row['prix_cny_formatted'] = $convertedPrice === null
            ? 'Taux non configuré'
            : grinco_admin_format_cny($convertedPrice);
        $row['date_creation_formatted'] = $row['date_creation'] === null
            ? 'Non disponible'
            : date('d/m/Y H:i', strtotime($row['date_creation']));
    }
    unset($row);

    return array(
        'rows' => $rows,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset
    );
}

function grinco_products_category_options()
{
    $statement = grinco_database()->prepare('SELECT id, nom, statut FROM categories ORDER BY nom ASC');
    $statement->execute();
    return $statement->fetchAll();
}

function grinco_products_brand_options()
{
    $statement = grinco_database()->prepare('SELECT id, nom FROM marques ORDER BY nom ASC');
    $statement->execute();
    return $statement->fetchAll();
}

function grinco_products_category_exists($categoryId)
{
    $statement = grinco_database()->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
    $statement->execute(array(':id' => (int) $categoryId));
    return (bool) $statement->fetchColumn();
}

function grinco_products_brand_exists($brandId)
{
    $statement = grinco_database()->prepare('SELECT id FROM marques WHERE id = :id LIMIT 1');
    $statement->execute(array(':id' => (int) $brandId));
    return (bool) $statement->fetchColumn();
}

function grinco_products_reference_exists($reference, $excludedId)
{
    $sql = 'SELECT id FROM produits WHERE reference = :reference';
    $parameters = array(':reference' => $reference);
    if ($excludedId > 0) {
        $sql .= ' AND id <> :id';
        $parameters[':id'] = (int) $excludedId;
    }
    $sql .= ' LIMIT 1';

    $statement = grinco_database()->prepare($sql);
    $statement->execute($parameters);
    return (bool) $statement->fetchColumn();
}

function grinco_products_exists($productId)
{
    $statement = grinco_database()->prepare('SELECT id FROM produits WHERE id = :id LIMIT 1');
    $statement->execute(array(':id' => (int) $productId));
    return (bool) $statement->fetchColumn();
}

function grinco_products_dependencies($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT '
        . '(SELECT COUNT(*) FROM produit_images WHERE produit_id = :image_product_id) AS images_count, '
        . '(SELECT COUNT(*) FROM produit_documents WHERE produit_id = :document_product_id) AS documents_count, '
        . '(SELECT COUNT(*) FROM demande_devis_details WHERE produit_id = :quote_product_id) AS quotes_count'
    );
    $statement->execute(array(
        ':image_product_id' => (int) $productId,
        ':document_product_id' => (int) $productId,
        ':quote_product_id' => (int) $productId
    ));
    $dependencies = $statement->fetch();

    return array(
        'images' => isset($dependencies['images_count']) ? (int) $dependencies['images_count'] : 0,
        'documents' => isset($dependencies['documents_count']) ? (int) $dependencies['documents_count'] : 0,
        'quotes' => isset($dependencies['quotes_count']) ? (int) $dependencies['quotes_count'] : 0
    );
}
