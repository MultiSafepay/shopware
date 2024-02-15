{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name="frontend_checkout_payment_fieldset_input_label"}
    {if $payment_mean.action == "MultiSafepayPayment"}
      <div class="method--label is--first">
        <label class="method--name is--strong" for="payment_mean{$payment_mean.id}">{$payment_mean.description}</label>
      </div>
        {if $payment_mean.name !== 'multisafepay_GENERIC'}
          <img style="margin-left:2.2rem" src="{$payment_mean.image}" alt="">
        {elseif $payment_mean.generic_image !== false}
          <img style="margin-left:2.2rem;max-width: 70px;max-height: 50px;object-fit: contain;" src="{$payment_mean.generic_image}" alt="">
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
