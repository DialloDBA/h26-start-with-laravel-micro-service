<?php

$targetDir = __DIR__ . '/storage/keys/';
$privateKey = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
]);


if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
openssl_pkey_export($privateKey, $privateKeyString);
file_put_contents($targetDir . 'private.pem', $privateKeyString);

echo "Clé privée générée et sauvegardée dans storage/keys/private.pem\n";

$publicKeyDetails = openssl_pkey_get_details($privateKey);
$publicKeyString = $publicKeyDetails['key'];
file_put_contents($targetDir . 'public.pem', $publicKeyString);

echo "Clé publique générée et sauvegardée dans storage/keys/public.pem\n";
