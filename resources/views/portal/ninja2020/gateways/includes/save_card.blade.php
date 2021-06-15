@php
    $token_billing = $gateway instanceof \App\Models\CompanyGateway
            ? $gateway->token_billing !== 'always'
            : $gateway->company_gateway->token_billing !== 'always';
@endphp

@if($token_billing)
    <div class="sm:grid px-4 py-5 sm:grid-cols-3 sm:gap-4 sm:px-6" id="save-card--container">
        <dt class="text-sm leading-5 font-medium text-gray-500">
            {{ ctrans('texts.save_payment_method_details') }}
        </dt>
        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
            <label class="mr-4">
                <input type="radio" class="form-radio cursor-pointer" name="token-billing-checkbox"
                       id="proxy_is_default"
                       value="true"/>
                <span class="ml-1 cursor-pointer">{{ ctrans('texts.yes') }}</span>
            </label>
            <labecoml>
                <input type="radio" class="form-radio cursor-pointer" name="token-billing-checkbox"
                       id="proxy_is_default"
                       value="false" checked />
                <span class="ml-1 cursor-pointer">{{ ctrans('texts.no') }}</span>
            </labecoml>
        </dd>
    </div>
@else
    <div id="save-card--container" class="hidden" style="display: none !important;">
        <input type="radio" class="form-radio cursor-pointer hidden" style="display: none !important;"
               name="token-billing-checkbox"
               id="proxy_is_default"
               value="true" checked hidden disabled/>
    </div>
@endif
