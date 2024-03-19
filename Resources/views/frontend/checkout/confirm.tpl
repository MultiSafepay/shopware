{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_index_header_css_screen"}
    {$smarty.block.parent}
    <link href="https://pay.multisafepay.com/sdk/components/v2/components.css" rel="stylesheet"
          type="text/css">
{/block}

{block name='frontend_checkout_confirm_submit'}
    <div class="information--panel-item" id="multisafepay-component">
        <div class="panel has--border block information--panel" style="border: 1px solid #dadae5;">
            <div class="panel--title is--underline payment--title">
                {s name="missing_details" namespace="frontend/multisafepay/component"}Fill in the missing payment data{/s}
            </div>
            <div class="panel--body is--wide payment--content" style="height: auto; border: 0">
                <div id="multisafepay-checkout" style="float:left; width: 100%">
                </div>
            </div>
        </div>
    </div>
    {$smarty.block.parent}
{/block}

{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    <script src="https://pay.multisafepay.com/sdk/components/v2/components.js"></script>
    <script>
        if (document.readyState !== 'loading') {
            this.setupComponent()
        } else {
            document.addEventListener('DOMContentLoaded', this.setupComponent.bind(this))
        }

        function setupComponent() {
            const self = this;

            {if !$component}
            document.getElementById('multisafepay-component').style.display = 'none';
            return;
            {/if}

            let multisafepayOptions = {
                debug: {if !$env} true {else} false {/if},
                env: {if !$env}'test'{else}'live'{/if},
                apiToken: '{$api_token}',
                order: {
                    currency: '{$currency}',
                    amount: {$sAmount} * 100,
                    customer: {
                        locale: '{$locale}',
                        country: '{$sUserData.additional.country.countryiso}',
                    },
                    template: {
                        settings: {
                            embed_mode: true
                        },
                        merge: true
                    },
                    {if $template_id}
                    payment_options: {
                        template_id: '{$template_id}'
                    }
                    {/if}
                }
            }

            this.multiSafepay = new MultiSafepay(multisafepayOptions)

            this.multiSafepay.init('payment', {
                container: '#multisafepay-checkout',
                gateway: '{$gateway_code}',
            });

            let form = document.getElementById('confirm--form')
            form.addEventListener('submit', function (event) {
                if (self.multiSafepay.getErrors().count > 0) {
                    event.preventDefault();
                    setTimeout(function () {
                        let submit = document.querySelector('button[form="confirm--form"]');
                        submit.removeAttribute('disabled')
                        document.querySelector('button[form="confirm--form"]').querySelector('div.js--loading').remove();
                        let i = document.createElement('i')
                        i.classList.add('icon--arrow-right')
                        submit.append(i)
                    }, 1000)

                    return false;
                }

                let input = document.createElement('input');
                input.setAttribute('name', 'payload');
                input.setAttribute('value', self.multiSafepay.getPaymentData().payload);
                input.setAttribute('type', 'hidden');
                form.appendChild(input);

                return true;
            })
        }

    </script>
{/block}
