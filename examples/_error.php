<?php /** @var \BankId\Merchant\Response\ErrorResponse $error */ ?>
<div class="alert alert-danger" role="alert">
    <p><strong>(iDx) Code:</strong> <?= e($error->errorCode) ?></p>
    <p><strong>(iDx) Message:</strong> <?= e($error->errorMessage) ?></p>
    <p><strong>(iDx) Details:</strong> <?= e($error->errorDetails) ?></p>
    <p><strong>(iDx) Consumer message:</strong> <?= e($error->consumerMessage) ?></p>
    <p><strong>(iDx) Suggested action:</strong> <?= e($error->suggestedAction) ?></p>
    <?php if ($error->additionalInformation !== null) { ?>
        <hr>
        <p><strong>(SAML) Status code (1):</strong> <?= e($error->additionalInformation->statusCodeFirstLevel) ?></p>
        <p><strong>(SAML) Status code (2):</strong> <?= e($error->additionalInformation->statusCodeSecondLevel) ?></p>
        <p><strong>(SAML) Status message:</strong> <?= e($error->additionalInformation->statusMessage) ?></p>
    <?php } ?>
</div>
