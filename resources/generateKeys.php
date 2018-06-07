<?php

/*
  generateKeys.php
  
  Utility to generate a Public/Private key pair.
  
*/

header("Content-Type: text/plain"); 

$config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 1024,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

// Create the private and public key
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privKey);
echo "$privKey\n";

// Extract the public key from $res to $pubKey
$pubKey = openssl_pkey_get_details($res);
$pubKey = $pubKey["key"];
echo $pubKey;
?>
