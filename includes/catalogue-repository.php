<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/catalogue-files.php';

function grinco_catalogue_public_select()
{
    return 'SELECT p.id, p.categorie_id, p.reference, p.nom, p.modele, p.description, '
        . 'c.nom AS categorie_nom, m.nom AS marque_nom, '
        . '(SELECT pi.image FROM produit_images pi WHERE pi.produit_id = p.id '
        . 'ORDER BY pi.image_principale DESC, pi.id ASC LIMIT 1) AS image_principale '
        . 'FROM produits p '
        . 'INNER JOIN categories c ON c.id = p.categorie_id '
        . 'INNER JOIN marques m ON m.id = p.marque_id ';
}

function grinco_catalogue_prepare_public_row($row)
{
    $row['id'] = (int) $row['id'];
    $row['modele'] = $row['modele'] === null ? '' : (string) $row['modele'];
    $row['description'] = $row['description'] === null ? '' : (string) $row['description'];
    $row['image_principale'] = $row['image_principale'] === null ? '' : (string) $row['image_principale'];
    $row['image_url'] = $row['image_principale'] === ''
        ? ''
        : grinco_catalogue_file_url($row['image_principale'], 'image');
    return $row;
}

function grinco_catalogue_excerpt($text, $maximum)
{
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') > $maximum
            ? rtrim(mb_substr($text, 0, $maximum - 1, 'UTF-8')) . '…'
            : $text;
    }
    return strlen($text) > $maximum ? rtrim(substr($text, 0, $maximum - 3)) . '...' : $text;
}

function grinco_catalogue_public_products()
{
    $statement = grinco_database()->prepare(
        grinco_catalogue_public_select() . 'WHERE c.statut = :category_status ORDER BY p.id DESC'
    );
    $statement->execute(array(':category_status' => 'Actif'));
    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $row = grinco_catalogue_prepare_public_row($row);
    }
    unset($row);
    return $rows;
}

function grinco_catalogue_public_product($productId)
{
    $statement = grinco_database()->prepare(
        grinco_catalogue_public_select()
        . 'WHERE p.id = :id AND c.statut = :category_status LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $productId, ':category_status' => 'Actif'));
    $row = $statement->fetch();
    return $row ? grinco_catalogue_prepare_public_row($row) : null;
}

function grinco_catalogue_public_products_by_ids($productIds)
{
    $cleanIds = array();
    foreach ($productIds as $productId) {
        if (is_numeric($productId) && (int) $productId > 0) {
            $cleanIds[(int) $productId] = (int) $productId;
        }
    }
    if (empty($cleanIds)) {
        return array();
    }

    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $statement = grinco_database()->prepare(
        grinco_catalogue_public_select()
        . 'WHERE p.id IN (' . $placeholders . ') AND c.statut = ?'
    );
    $parameters = array_values($cleanIds);
    $parameters[] = 'Actif';
    $statement->execute($parameters);
    $indexed = array();
    foreach ($statement->fetchAll() as $row) {
        $row = grinco_catalogue_prepare_public_row($row);
        $indexed[$row['id']] = $row;
    }
    return $indexed;
}

function grinco_catalogue_public_category_by_name($categoryName)
{
    $statement = grinco_database()->prepare(
        'SELECT id, nom FROM categories WHERE nom = :name AND statut = :status LIMIT 1'
    );
    $statement->execute(array(':name' => (string) $categoryName, ':status' => 'Actif'));
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int) $row['id'];
    return $row;
}

function grinco_catalogue_public_page($requestedPage, $perPage, $search, $categoryId)
{
    $connection = grinco_database();
    $page = is_int($requestedPage) && $requestedPage > 0 ? $requestedPage : 1;
    $perPage = is_int($perPage) && $perPage > 0 ? $perPage : 12;
    $search = trim((string) $search);
    $categoryId = (int) $categoryId;
    $where = array('c.statut = :category_status');
    $parameters = array(':category_status' => 'Actif');

    if ($categoryId > 0) {
        $where[] = 'p.categorie_id = :category_id';
        $parameters[':category_id'] = $categoryId;
    }
    if ($search !== '') {
        $where[] = '(p.nom LIKE :search_name OR p.reference LIKE :search_reference '
            . 'OR COALESCE(p.modele, \'\') LIKE :search_model OR m.nom LIKE :search_brand)';
        $pattern = '%' . $search . '%';
        $parameters[':search_name'] = $pattern;
        $parameters[':search_reference'] = $pattern;
        $parameters[':search_model'] = $pattern;
        $parameters[':search_brand'] = $pattern;
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $fromSql = ' FROM produits p INNER JOIN categories c ON c.id = p.categorie_id '
        . 'INNER JOIN marques m ON m.id = p.marque_id';
    $countStatement = $connection->prepare('SELECT COUNT(*)' . $fromSql . $whereSql);
    $countStatement->execute($parameters);
    $total = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $statement = $connection->prepare(
        grinco_catalogue_public_select() . $whereSql . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, $key === ':category_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $row = grinco_catalogue_prepare_public_row($row);
    }
    unset($row);

    return array(
        'rows' => $rows,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages
    );
}

function grinco_catalogue_public_product_images($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, image, image_principale FROM produit_images '
        . 'WHERE produit_id = :product_id ORDER BY image_principale DESC, id ASC'
    );
    $statement->execute(array(':product_id' => (int) $productId));
    $images = array();
    foreach ($statement->fetchAll() as $row) {
        $url = grinco_catalogue_file_url($row['image'], 'image');
        if ($url !== '') {
            $images[] = array(
                'id' => (int) $row['id'],
                'url' => $url,
                'is_primary' => (int) $row['image_principale'] === 1
            );
        }
    }
    return $images;
}

function grinco_catalogue_public_product_documents($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT id FROM produit_documents WHERE produit_id = :product_id ORDER BY id ASC'
    );
    $statement->execute(array(':product_id' => (int) $productId));
    $documents = array();
    foreach ($statement->fetchAll() as $index => $row) {
        $documents[] = array(
            'id' => (int) $row['id'],
            'label' => 'Document produit ' . ($index + 1)
        );
    }
    return $documents;
}
