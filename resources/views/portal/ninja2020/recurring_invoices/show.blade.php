@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.recurring_invoice'))

@section('body')
    <div class="container mx-auto">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ ctrans('texts.recurring_invoices') }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500" translate>
                    {{ ctrans('texts.details_of_recurring_invoice') }}.
                </p>
            </div>
            <div>
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm leading-5 font-medium text-gray-500">
                            {{ ctrans('texts.start_date') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $invoice->formatDate($invoice->start_date, $invoice->client->date_format()) }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm leading-5 font-medium text-gray-500">
                            {{ ctrans('texts.next_send_date') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $invoice->formatDate($invoice->next_send_date, $invoice->client->date_format()) }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm leading-5 font-medium text-gray-500">
                            {{ ctrans('texts.frequency') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ \App\Models\RecurringInvoice::frequencyForKey($invoice->frequency_id) }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm leading-5 font-medium text-gray-500">
                            {{ ctrans('texts.cycles_remaining') }}
                        </dt>
                        <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $invoice->remaining_cycles == '-1' ? ctrans('texts.endless') : $invoice->remaining_cycles }}
                            @if($invoice->remaining_cycles == '-1') &#8734; @endif
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm leading-5 font-medium text-gray-500">
                            {{ ctrans('texts.amount') }}
                        </dt>
                        <div class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ \App\Utils\Number::formatMoney($invoice->amount, $invoice->client) }}
                        </div>
                    </div>
                </dl>
            </div>
        </div>

        @if(is_null($invoice->subscription_id) || optional($invoice->subscription)->allow_cancellation)
            <div class="bg-white shadow sm:rounded-lg mt-4">
                <div class="px-4 py-5 sm:p-6">
                    <div class="sm:flex sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ ctrans('texts.cancellation') }}
                            </h3>
                            <div class="mt-2 max-w-xl text-sm leading-5 text-gray-500">
                                <p translate>
                                    {{ ctrans('texts.about_cancellation') }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex-shrink-0 sm:flex sm:items-center">
                            <div class="inline-flex rounded-md shadow-sm" x-data="{ open: false }">
                                <button class="button button-danger" translate @click="open = true">Request Cancellation
                                </button>
                                @include('portal.ninja2020.recurring_invoices.includes.modals.cancellation')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($invoice->subscription && $invoice->subscription->allow_plan_changes)
            <div class="bg-white shadow overflow-hidden px-4 py-5 lg:rounded-lg mt-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Switch Plans:</h3>
                <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">Upgrade or downgrade your current plan.</p>

                <div class="flex mt-4 space-x-2">
                    @foreach($invoice->subscription->service()->getPlans() as $subscription)
                        <a href="{{ route('client.subscription.plan_switch', ['recurring_invoice' => $invoice->hashed_id, 'target' => $subscription->hashed_id]) }}" class="border rounded px-5 py-2 hover:border-gray-800 text-sm cursor-pointer">{{ $subscription->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
