<?php /** @var \BankId\Merchant\Configuration\Configuration $config */ ?>
<div class="mb-3">
    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#merchantOptions">Toggle merchant options</button>
</div>
<div class="collapse mb-3" id="merchantOptions">
    <div class="row g-3">
        <div class="col-md-4">
            <label for="BankId.Merchant.MerchantID" class="form-label">MerchantID</label>
            <input class="form-control" id="BankId.Merchant.MerchantID" name="BankId.Merchant.MerchantID" type="text" value="<?= e($config->merchantID) ?>">
        </div>
        <div class="col-md-4">
            <label for="BankId.Merchant.SubID" class="form-label">SubID</label>
            <input class="form-control" id="BankId.Merchant.SubID" name="BankId.Merchant.SubID" type="text" value="<?= e($config->merchantSubID) ?>">
        </div>
        <div class="col-md-4">
            <label for="BankId.Merchant.ReturnUrl" class="form-label">ReturnUrl</label>
            <input class="form-control" id="BankId.Merchant.ReturnUrl" name="BankId.Merchant.ReturnUrl" type="text" value="<?= e($config->merchantReturnUrl) ?>">
        </div>
    </div>
</div>
