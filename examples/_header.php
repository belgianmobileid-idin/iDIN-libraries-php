<?php
require_once __DIR__ . '/../vendor/autoload.php';

use BankId\Merchant\Configuration\Configuration;

$config = Configuration::load('bankid-config.xml');

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function applyPostOverrides(Configuration $config): void {
    $map = [
        'BankId_Merchant_MerchantID' => 'merchantID',
        'BankId_Merchant_SubID' => 'merchantSubID',
        'BankId_Merchant_ReturnUrl' => 'merchantReturnUrl',
        'BankId_Acquirer_DirectoryUrl' => 'acquirerDirectoryUrl',
        'BankId_Acquirer_TransactionUrl' => 'acquirerTransactionUrl',
        'BankId_Acquirer_StatusUrl' => 'acquirerStatusUrl',
    ];
    foreach ($map as $postKey => $prop) {
        if (isset($_POST[$postKey])) {
            $config->$prop = $_POST[$postKey];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>iDIN Merchant Website - PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body { padding-top: 70px; padding-bottom: 20px; }
        textarea { font-family: monospace; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">iDIN Merchant - PHP</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="directory.php">Directory</a></li>
                    <li class="nav-item"><a class="nav-link" href="authenticate.php">Authenticate</a></li>
                    <li class="nav-item"><a class="nav-link" href="getresponse.php">GetResponse</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
