<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a href="{$publicId}" title="{l s='Pay with Wooppay' mod='wooppay'}" class="wooppay">
                {l s='Pay with Wooppay' mod='wooppay'}
            </a>
        </p>
    </div>
</div>
<style>
    p.payment_module a.wooppay {
        background: url({$path}views/img/logo.png) 23px 20px no-repeat;
    }
    p.payment_module a.wooppay:hover {
        background-color: #f6f6f6;
    }
</style>