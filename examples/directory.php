<?php
include '_header.php';

use BankId\Merchant\Communicator\Communicator;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    applyPostOverrides($config);
    $Model = (new Communicator($config))->getDirectory();
}
?>

<h2>Directory</h2>
<hr>

<form method="post">
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="BankId.Acquirer.DirectoryUrl" class="form-label">BankId.Acquirer.DirectoryUrl</label>
            <input class="form-control" id="BankId.Acquirer.DirectoryUrl" name="BankId.Acquirer.DirectoryUrl" type="text" value="<?= e($config->acquirerDirectoryUrl) ?>">
        </div>
    </div>

    <?php include '_merchant_fields.php'; ?>

    <button type="submit" class="btn btn-primary">Send directory request</button>
</form>

<hr>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($Model->isError) {
        $error = $Model->error;
        include '_error.php';
    } else { ?>
        <select class="form-select mb-3">
            <option>Choose a bank</option>
            <?php foreach ($Model->getIssuersByCountry() as $country => $issuers) { ?>
                <optgroup label="<?= e($country) ?>">
                    <?php foreach ($issuers as $issuer) { ?>
                        <option value="<?= e($issuer->id) ?>"><?= e($issuer->name) ?></option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>

        <textarea readonly class="form-control font-monospace" rows="10"><?= e($Model->rawMessage) ?></textarea>
    <?php }
} ?>

<?php include '_footer.php'; ?>
