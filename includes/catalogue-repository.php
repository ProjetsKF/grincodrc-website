<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/catalogue-files.php';

function grinco_catalogue_public_select()
{
    return 'SELECT p.id, p.reference, p.nom, p.modele, p.description, '
        . 'c.nom AS categorie_nom, m.nom AS marque_nom, '
        . '(SELECT pi.image FROM produit_images pi WHERE pi.produit_id = p.id '
        . 'AND pi.image_principale = 1 ORDER BY pi.id ASC LIMIT 1) AS image_principale '
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

function grinco_catalogue_public_products()
{
    $statement = grinco_database()->prepare(grinco_catalogue_public_select() . 'ORDER BY p.id DESC');
    $statement->execute();
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
        grinco_catalogue_public_select() . 'WHERE p.id = :id LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $productId));
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
        grinco_catalogue_public_select() . 'WHERE p.id IN (' . $placeholders . ')'
    );
    $statement->execute(array_values($cleanIds));
    $indexed = array();
    foreach ($statement->fetchAll() as $row) {
        $row = grinco_catalogue_prepare_public_row($row);
        $indexed[$row['id']] = $row;
    }
    return $indexed;
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
