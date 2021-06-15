@extends('portal.ninja2020.layout.app')
@section('meta_title', ucfirst($payment_method->gateway_type->name))

@section('body')
    <div class="container mx-auto">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    {{ ctrans("texts.{$payment_method->gateway_type->alias}") }}
                </h3>
                <p class="max-w-2xl mt-1 text-sm leading-5 text-gray-500" translate>
                    {{ ctrans('texts.payment_method_details') }}
                </p>
            </div>
            <div>
                <dl>
                    @if(!empty($payment_method->gateway_type->name) && !is_null($payment_method->gateway_type->name))
                    <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium leading-5 text-gray-500">
                            {{ ctrans('texts.payment_type') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ ucfirst($payment_method->gateway_type->name) }}
                        </dd>
                    </div>
                    @endif

                    @if(!empty($payment_method->meta) && !is_null($payment_method->meta))
                    <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium leading-5 text-gray-500">
                            {{ ctrans('texts.type') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ optional($payment_method->meta)->brand }}
                            {{ optional($payment_method->meta)->scheme }}
                        </dd>
                    </div>
                    @endif

                    @if(!empty($payment_method->meta->last4) && !is_null($payment_method->meta->last4))
                    <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium leading-5 text-gray-500">
                            {{ ctrans('texts.card_number') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            **** {{ ucfirst($payment_method->meta->last4) }}
                        </dd>
                    </div>
                    @endif
                    
                    @if(!empty($payment_method->created_at) && !is_null($payment_method->created_at))
                    <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium leading-5 text-gray-500">
                            {{ ctrans('texts.date_created') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $payment_method->formatDateTimestamp($payment_method->created_at, auth()->user()->client->date_format()) }}
                        </dd>
                    </div>
                    @endif

                    @if(!empty($payment_method->is_default) && !is_null($payment_method->is_default))
                    <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium leading-5 text-gray-500">
                            {{ ctrans('texts.default') }}
                        </dt>
                        <div class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $payment_method->is_default ? ctrans('texts.yes') : ctrans('texts.no') }}
                        </div>
                    </div>
                    @endif
                    
                    @isset($payment_method->meta->exp_month)
                        <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium leading-5 text-gray-500">
                                {{ ctrans('texts.expires') }}
                            </dt>
                            <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                                {{ $payment_method->meta->exp_month }} / {{ $payment_method->meta->exp_year }}
                            </dd>
                        </div>
                    @endisset
                </dl>
            </div>
        </div>
        <div class="mt-4 mb-4 bg-white shadow sm:rounded-lg" translate>
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:flex sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            {{ ctrans('texts.remove')}}
                        </h3>
                        <div class="max-w-xl mt-2 text-sm leading-5 text-gray-500">
                            <p>
                                {{ ctrans('texts.permanently_remove_payment_method') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex-shrink-0 sm:flex sm:items-center">
                        <div class="inline-flex rounded-md shadow-sm" x-data="{ open: false }">
                            <button class="button button-danger" @click="open = true" id="open-delete-popup">
                                {{ ctrans('texts.remove_payment_method') }}
                            </button>
                            @include('portal.ninja2020.payment_methods.includes.modals.removal')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
