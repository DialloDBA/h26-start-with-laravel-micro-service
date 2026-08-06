<?php

$targetDir = __DIR__ . '/storage/keys/';

$privateKeyFile = $targetDir . 'private.pem';
$publicKeyFile = $targetDir . 'public.pem';

$config = [
    "digest_alg" => "sha256",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
];

if (file_exists($privateKeyFile) && file_exists($publicKeyFile)) {
    echo "Les clés existent déjà. Aucune action n'est nécessaire.\n";
    exit;
}

$privateKey = openssl_pkey_new($config);

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (!$privateKey) {
    echo "Erreur lors de la génération de la clé privée.\n";
    exit;
}

if (!openssl_pkey_export($privateKey, $privateKeyString)) {
    echo "Erreur lors de l'exportation de la clé privée.\n";
    exit;
}

file_put_contents($privateKeyFile, $privateKeyString, LOCK_EX);

echo "Clé privée générée et sauvegardée dans storage/keys/private.pem\n";

$publicKeyDetails = openssl_pkey_get_details($privateKey);

$publicKeyString = $publicKeyDetails['key'];

file_put_contents($publicKeyFile, $publicKeyString, LOCK_EX);

echo "Clé publique générée et sauvegardée dans storage/keys/public.pem\n";
