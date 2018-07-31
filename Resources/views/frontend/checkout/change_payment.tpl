{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name="frontend_checkout_payment_fieldset_input_label"}
    {if $payment_mean.action == "MultiSafepayPayment"}
        <div class="method--label is--first">
            <label class="method--name is--strong" for="payment_mean{$payment_mean.id}">{$payment_mean.description}</label>
        </div>
        <img style="margin-left:2.2rem" src="{link file="frontend/_public/src/img/{$payment_mean.msp_logo_locale}/{$payment_mean.name|lower}.png"}">
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
