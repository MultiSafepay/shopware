{namespace name='frontend/multisafepay/ideal'}

{block name='frontend_plugins_payment_multisafepay_ideal_issuers'}
    {if $Controller == "checkout"}
        <div class="select-field">
            <select id="ideal_issuers_list" name="ideal_issuers">
                <option selected disabled>{s name="chooseYourBank"}Choose your bank...{/s}</option>
                {foreach $idealIssuers as $idealIssuer}
                    <option value="{$idealIssuer->code}"
                        {if $currentIssuer == $idealIssuer->code}
                            selected="selected"
                        {/if}
                    >
                        {$idealIssuer->description}
                    </option>
                {/foreach}
            </select>
        </div>
    {/if}
{/block}
