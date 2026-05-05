<?php
include '_header.php';

use BankId\Merchant\Communicator\Communicator;
use BankId\Merchant\Request\AuthenticationRequest;
use BankId\Merchant\Enum\AssuranceLevel;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    applyPostOverrides($config);

    $a = new AuthenticationRequest();
    $a->issuerID = $_POST['issuerID'];
    $a->language = $_POST['language'];
    $a->expirationPeriod = $_POST['expirationPeriod'] ?: null;
    $a->entranceCode = $_POST['entranceCode'];
    $a->merchantReference = $_POST['BankId_MerchantReference'];
    $a->requestedServiceID = $_POST['BankId_RequestedServiceId'];
    $a->assuranceLevel = $_POST['BankId_LOA'];
    $a->documentID = $_POST['DocumentID'] ?? '';

    $Model = (new Communicator($config))->newAuthenticationRequest($a);
}
?>

<h2>Authenticate</h2>
<hr>

<form method="post">
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="BankId.Acquirer.TransactionUrl" class="form-label">BankId.Acquirer.TransactionUrl</label>
            <input class="form-control" id="BankId.Acquirer.TransactionUrl" name="BankId.Acquirer.TransactionUrl" type="text" value="<?= e($config->acquirerTransactionUrl) ?>">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="issuerID" class="form-label">issuerID</label>
            <input class="form-control" id="issuerID" name="issuerID" type="text" value="INGBNL2A">
        </div>
        <div class="col-md-3">
            <label for="expirationPeriod" class="form-label">expirationPeriod</label>
            <input class="form-control" id="expirationPeriod" name="expirationPeriod" type="text" value="PT5M">
        </div>
        <div class="col-md-3">
            <label for="language" class="form-label">language</label>
            <select class="form-select" id="language" name="language">
                <option>en</option>
                <option selected>nl</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="entranceCode" class="form-label">entranceCode</label>
            <input class="form-control" id="entranceCode" name="entranceCode" type="text" value="entranceCode">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="BankId.MerchantReference" class="form-label">MerchantReference</label>
            <input class="form-control" id="BankId.MerchantReference" name="BankId.MerchantReference" type="text" value="merchantReference">
        </div>
        <div class="col-md-3">
            <label for="BankId.RequestedServiceId" class="form-label">RequestedServiceId</label>
            <input class="form-control" id="BankId.RequestedServiceId" name="BankId.RequestedServiceId" type="text" value="21968">
        </div>
        <div class="col-md-3">
            <label for="BankId.LOA" class="form-label">LOA</label>
            <select class="form-select" id="BankId.LOA" name="BankId.LOA">
                <option selected><?= e(AssuranceLevel::Loa3->value) ?></option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="DocumentID" class="form-label">DocumentID</label>
            <input class="form-control" id="DocumentID" name="DocumentID" type="text" value="">
        </div>
    </div>

    <?php include '_merchant_fields.php'; ?>

    <button type="submit" class="btn btn-primary">Send authentication request</button>
</form>

<hr>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($Model->isError) {
        $error = $Model->error;
        include '_error.php';
    } else { ?>
        <pre>IssuerAuthenticationUrl = <?= e($Model->issuerAuthenticationURL) ?></pre>
        <pre>TransactionID = <?= e($Model->transactionID) ?></pre>
        <pre>TransactionCreateDateTimestamp = <?= e($Model->transactionCreateDateTimestamp) ?></pre>

        <hr>
        <textarea readonly class="form-control font-monospace" rows="10"><?= e($Model->rawMessage) ?></textarea>
    <?php }
} ?>

<?php include '_footer.php'; ?>
