/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

class ProcessSOFORT {
    constructor(key, stripeConnect) {
        this.key = key;
        this.errors = document.getElementById('errors');
        this.stripeConnect = stripeConnect;
    }

    setupStripe = () => {
        this.stripe = Stripe(this.key);

        if(this.stripeConnect)
            this.stripe.stripeAccount = stripeConnect;

        return this;
    };

    handle = () => {
        let data = {
            type: 'sofort',
            amount: document.querySelector('meta[name="amount"]').content,
            currency: 'eur',
            redirect: {
                return_url: document.querySelector('meta[name="return-url"]')
                    .content,
            },
            sofort: {
                country: document.querySelector('meta[name="country"]').content,
            },
        };

        document.getElementById('pay-now').addEventListener('click', (e) => {
            document.getElementById('pay-now').disabled = true;
            document.querySelector('#pay-now > svg').classList.remove('hidden');
            document.querySelector('#pay-now > span').classList.add('hidden');

            this.stripe.createSource(data).then(function(result) {
                if (result.hasOwnProperty('source')) {
                    return (window.location = result.source.redirect.url);
                }

                document.getElementById('pay-now').disabled = false;
                document.querySelector('#pay-now > svg').classList.add('hidden');
                document.querySelector('#pay-now > span').classList.remove('hidden');

                this.errors.textContent = '';
                this.errors.textContent = result.error.message;
                this.errors.hidden = false;

                document.getElementById('pay-now').disabled = false;
            });
        });
    };
}

const publishableKey = document.querySelector(
    'meta[name="stripe-publishable-key"]'
)?.content ?? '';

const stripeConnect = 
    document.querySelector('meta[name="stripe-account-id"]')?.content ?? ''; 

new ProcessSOFORT(publishableKey, stripeConnect).setupStripe().handle();
