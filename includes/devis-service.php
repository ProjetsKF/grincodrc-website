<?php

require_once __DIR__ . '/devis-panier.php';

function grinco_quote_submission_products($cart)
{
    if (empty($cart) || !is_array($cart)) {
        throw new RuntimeException('La sélection de produits est vide.');
    }
    $products = grinco_catalogue_public_products_by_ids(array_keys($cart));
    if (count($products) !== count($cart)) {
        throw new RuntimeException('Un produit sélectionné n’est plus disponible.');
    }

    $ordered = array();
    foreach ($cart as $productId => $quantity) {
        if (
            !ctype_digit((string) $productId)
            || !ctype_digit((string) $quantity)
            || (int) $quantity < 1
            || (int) $quantity > grinco_quote_cart_max_quantity()
            || !isset($products[(int) $productId])
        ) {
            throw new RuntimeException('La sélection de produits contient une donnée invalide.');
        }
        $product = $products[(int) $productId];
        $product['quantite'] = (int) $quantity;
        $ordered[] = $product;
    }
    return $ordered;
}

function grinco_quote_persist_request($data, $products)
{
    $connection = grinco_database();
    $connection->beginTransaction();
    try {
        $requestStatement = $connection->prepare(
            'INSERT INTO demandes_devis (nom, entreprise, telephone, email, message) '
            . 'VALUES (:name, :company, :phone, :email, :message)'
        );
        $requestStatement->execute(array(
            ':name' => $data['nom'],
            ':company' => $data['entreprise'] === '' ? null : $data['entreprise'],
            ':phone' => $data['telephone'],
            ':email' => $data['email'] === '' ? null : $data['email'],
            ':message' => $data['message'] === '' ? null : $data['message']
        ));
        $requestId = (int) $connection->lastInsertId();
        if ($requestId <= 0) {
            throw new RuntimeException('La demande n’a pas pu être créée.');
        }

        $detailStatement = $connection->prepare(
            'INSERT INTO demande_devis_details (demande_id, produit_id, quantite) '
            . 'VALUES (:request_id, :product_id, :quantity)'
        );
        foreach ($products as $product) {
            $detailStatement->execute(array(
                ':request_id' => $requestId,
                ':product_id' => (int) $product['id'],
                ':quantity' => (int) $product['quantite']
            ));
        }
        $dateStatement = $connection->prepare('SELECT date_demande FROM demandes_devis WHERE id = :id');
        $dateStatement->execute(array(':id' => $requestId));
        $requestDate = (string) $dateStatement->fetchColumn();
        if ($requestDate === '') {
            throw new RuntimeException('La date de la demande est indisponible.');
        }
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }

    return array(
        'id' => $requestId,
        'date_demande' => $requestDate,
        'products' => $products
    );
}

function grinco_quote_create_request($data, $cart)
{
    return grinco_quote_persist_request($data, grinco_quote_submission_products($cart));
}
