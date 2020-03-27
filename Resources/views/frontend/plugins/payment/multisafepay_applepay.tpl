{block name='frontend_plugins_payment_multisafepay_applepay'}
    {if $sTargetAction == "shippingPayment"}
        <script>
            var applePayBlock = document.getElementsByClassName('payment_logo_multisafepay_APPLEPAY')[0].parentElement;
            applePayBlock.style.display = 'none';

            try {
                if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
                    applePayBlock.style.display = 'block';
                }
            } catch (error) {
                console.warn('MultiSafepay error when trying to initialize Apple Pay:', error);
            }
        </script>
    {/if}
{/block}
