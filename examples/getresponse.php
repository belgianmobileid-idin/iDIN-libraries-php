<?php
include '_header.php';

use BankId\Merchant\Communicator\Communicator;
use BankId\Merchant\Enum\TransactionStatus;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    applyPostOverrides($config);
    $Model = (new Communicator($config))->getResponse($_POST['transactionID']);
}
?>

<h2>GetResponse</h2>
<hr>

<form method="post">
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="BankId.Acquirer.StatusUrl" class="form-label">BankId.Acquirer.StatusUrl</label>
            <input class="form-control" id="BankId.Acquirer.StatusUrl" name="BankId.Acquirer.StatusUrl" type="text" value="<?= e($config->acquirerStatusUrl) ?>">
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="transactionID" class="form-label">transactionID</label>
            <input class="form-control" id="transactionID" name="transactionID" type="text" value="1234000000002144">
        </div>
    </div>

    <?php include '_merchant_fields.php'; ?>

    <button type="submit" class="btn btn-primary">Send status request</button>
</form>

<hr>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($Model->isError) {
        $error = $Model->error;
        include '_error.php';
    } else { ?>
        <pre>StatusCodeFirstLevel = <?= e($Model->samlResponse->status->statusCodeFirstLevel) ?></pre>
        <pre>StatusCodeSecondLevel = <?= e($Model->samlResponse->status->statusCodeSecondLevel) ?></pre>
        <hr>

        <?php if ($Model->status === TransactionStatus::Success) { ?>
            <p><strong>Attributes:</strong></p>
            <?php foreach ($Model->samlResponse->attributes as $key => $value) { ?>
                <pre><?= e($key) ?> = <?= e($value) ?></pre>
            <?php } ?>
        <?php } else { ?>
            <pre>Status = <?= e($Model->status->value) ?></pre>
        <?php } ?>

        <textarea readonly class="form-control font-monospace" rows="10"><?= e($Model->rawMessage) ?></textarea>
    <?php }
} ?>

<?php include '_footer.php'; ?>
