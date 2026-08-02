<?php

function grinco_quote_notification_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function grinco_quote_send_notification($request, $products, $configurationOverride = null)
{
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
    $configPath = dirname(__DIR__) . '/config/mail.php';
    if (!is_file($autoloadPath) || ($configurationOverride === null && !is_file($configPath))) {
        throw new RuntimeException('La messagerie n’est pas configurée.');
    }
    require_once $autoloadPath;
    $config = $configurationOverride === null ? require $configPath : $configurationOverride;
    if (!is_array($config)) {
        throw new RuntimeException('La configuration SMTP est invalide.');
    }
    foreach (array('host', 'port', 'username', 'password', 'from_email', 'from_name', 'recipient_email') as $key) {
        if (!isset($config[$key]) || trim((string) $config[$key]) === '') {
            throw new RuntimeException('La configuration SMTP est incomplète.');
        }
    }

    $productRows = '';
    $plainProducts = '';
    foreach ($products as $product) {
        $productRows .= '<tr><td style="padding:9px;border-bottom:1px solid #e5e9e6;">'
            . grinco_quote_notification_escape($product['reference']) . '</td><td style="padding:9px;border-bottom:1px solid #e5e9e6;">'
            . grinco_quote_notification_escape($product['nom']) . '</td><td style="padding:9px;border-bottom:1px solid #e5e9e6;text-align:center;">'
            . (int) $product['quantite'] . '</td></tr>';
        $plainProducts .= '- ' . $product['reference'] . ' — ' . $product['nom'] . ' : ' . (int) $product['quantite'] . "\n";
    }

    $reference = 'DEVIS-' . str_pad((string) $request['id'], 6, '0', STR_PAD_LEFT);
    $body = '<!doctype html><html lang="fr"><body style="margin:0;background:#f5f7f5;font-family:Arial,sans-serif;color:#252525;">'
        . '<div style="max-width:720px;margin:25px auto;background:#fff;border-top:6px solid #3A884C;border-radius:10px;padding:28px;">'
        . '<h1 style="margin:0 0 6px;color:#3A884C;font-size:24px;">Nouvelle demande de devis</h1><p style="color:#69736d;">Référence ' . $reference . '</p>'
        . '<h2 style="font-size:16px;">Informations client</h2><p><strong>Nom :</strong> ' . grinco_quote_notification_escape($request['nom'])
        . '<br><strong>Entreprise :</strong> ' . grinco_quote_notification_escape($request['entreprise'] === '' ? 'Non renseignée' : $request['entreprise'])
        . '<br><strong>Téléphone :</strong> ' . grinco_quote_notification_escape($request['telephone'])
        . '<br><strong>E-mail :</strong> ' . grinco_quote_notification_escape($request['email'] === '' ? 'Non renseigné' : $request['email'])
        . '<br><strong>Date :</strong> ' . grinco_quote_notification_escape($request['date_demande']) . '</p>'
        . '<p><strong>Message :</strong><br>' . nl2br(grinco_quote_notification_escape($request['message'] === '' ? 'Aucun message' : $request['message'])) . '</p>'
        . '<h2 style="font-size:16px;">Produits demandés</h2><table style="width:100%;border-collapse:collapse;"><thead><tr><th style="padding:9px;text-align:left;background:#edf6ef;">Référence</th><th style="padding:9px;text-align:left;background:#edf6ef;">Produit</th><th style="padding:9px;background:#edf6ef;">Quantité</th></tr></thead><tbody>' . $productRows . '</tbody></table>'
        . '</div></body></html>';
    $plain = "Nouvelle demande de devis " . $reference . "\n"
        . 'Nom : ' . $request['nom'] . "\nEntreprise : " . $request['entreprise'] . "\nTéléphone : " . $request['telephone']
        . "\nE-mail : " . $request['email'] . "\nDate : " . $request['date_demande'] . "\nMessage : " . $request['message']
        . "\n\nProduits :\n" . $plainProducts;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['username'];
    $mail->Password = (string) $config['password'];
    $mail->Port = (int) $config['port'];
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';
    $encryption = isset($config['encryption']) ? strtolower(trim((string) $config['encryption'])) : '';
    if ($encryption === 'ssl' || $encryption === 'smtps') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls' || $encryption === 'starttls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->setFrom((string) $config['from_email'], (string) $config['from_name']);
    $mail->addAddress((string) $config['recipient_email']);
    if ($request['email'] !== '') {
        $mail->addReplyTo($request['email'], $request['nom']);
    }
    $mail->isHTML(true);
    $mail->Subject = 'Nouvelle demande de devis ' . $reference;
    $mail->Body = $body;
    $mail->AltBody = $plain;
    return $mail->send();
}
