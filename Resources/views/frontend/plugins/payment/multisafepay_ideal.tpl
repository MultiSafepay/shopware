{block name='frontend_plugins_payment_multisafepay_ideal_issuers'}
    {if $sTargetAction == "shippingPayment"}
        <div class="select-field">
            <select id="ideal_issuers_list" name="ideal_issuers" data-auto-submit="true">
                <option selected disabled>Choose your bank...</option>
                {foreach $idealIssuers as $idealIssuer}
                    <option value="{$idealIssuer->code}">
                        {$idealIssuer->description}
                    </option>
                {/foreach}
            </select>
        </div>
    {/if}
{/block}