<div>
    <p class="mb-4 uppercase leading-4 tracking-wide inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white"
       translate="yes">
        One-time payments
    </p>

    <div class="flex items-center justify-between mt-4">
        <div class="flex items-center">
            <span class="mr-2 text-sm hidden md:block">{{ ctrans('texts.per_page') }}</span>
            <select wire:model="per_page" class="form-select py-1 text-sm">
                <option>5</option>
                <option selected>10</option>
                <option>15</option>
                <option>20</option>
            </select>
        </div>
    </div>
    <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="align-middle inline-block min-w-full overflow-hidden rounded">
            <table class="min-w-full shadow rounded border border-gray-200 mt-4 credits-table">
                <thead>
                <tr>
                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-white uppercase border-b border-gray-200 bg-primary">
                            <span role="button" wire:click="sortBy('number')" class="cursor-pointer">
                                {{ ctrans('texts.invoice') }}
                            </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-primary text-left text-xs leading-4 font-medium text-white uppercase tracking-wider">
                            <span role="button" wire:click="sortBy('amount')" class="cursor-pointer">
                                {{ ctrans('texts.total') }}
                            </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-primary text-left text-xs leading-4 font-medium text-white uppercase tracking-wider">
                            <span role="button" wire:click="sortBy('public_notes')" class="cursor-pointer">
                                {{ ctrans('texts.date') }}
                            </span>
                    </th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr class="bg-white group hover:bg-gray-100">
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            <a href="{{ route('client.invoice.show', $invoice->hashed_id) }}"
                               class="button-link text-primary">
                                {{ $invoice->number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ App\Utils\Number::formatMoney($invoice->amount, $invoice->client) }}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ $invoice->formatDate($invoice->date, $invoice->client->date_format()) }}
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white group hover:bg-gray-100">
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500" colspan="100%">
                            {{ ctrans('texts.no_results') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center md:justify-between mt-6 mb-6">
        @if($invoices->total() > 0)
            <span class="text-gray-700 text-sm hidden md:block">
                {{ ctrans('texts.showing_x_of', ['first' => $invoices->firstItem(), 'last' => $invoices->lastItem(), 'total' => $invoices->total()]) }}
            </span>
        @endif
        {{ $invoices->links('portal/ninja2020/vendor/pagination') }}
    </div>

    <p class="mb-4 uppercase leading-4 tracking-wide inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white mt-4"
       translate="yes">
        Subscriptions
    </p>

    <div class="flex items-center justify-between mt-4">
        <div class="flex items-center">
            <span class="mr-2 text-sm hidden md:block">{{ ctrans('texts.per_page') }}</span>
            <select wire:model="per_page" class="form-select py-1 text-sm">
                <option>5</option>
                <option selected>10</option>
                <option>15</option>
                <option>20</option>
            </select>
        </div>
    </div>
    <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="align-middle inline-block min-w-full overflow-hidden rounded">
            <table class="min-w-full shadow rounded border border-gray-200 mt-4 credits-table">
                <thead>
                <tr>
                    <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-white uppercase border-b border-gray-200 bg-primary">
                            <span role="button" wire:click="sortBy('number')" class="cursor-pointer">
                                {{ ctrans('texts.invoice') }}
                            </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-primary text-left text-xs leading-4 font-medium text-white uppercase tracking-wider">
                            <span role="button" wire:click="sortBy('amount')" class="cursor-pointer">
                                {{ ctrans('texts.total') }}
                            </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-primary text-left text-xs leading-4 font-medium text-white uppercase tracking-wider">
                            <span role="button" wire:click="sortBy('public_notes')" class="cursor-pointer">
                                {{ ctrans('texts.date') }}
                            </span>
                    </th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr class="bg-white group hover:bg-gray-100">
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            <a href="{{ route('client.invoice.show', $invoice->hashed_id) }}"
                               class="button-link text-primary">
                                {{ $invoice->number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ App\Utils\Number::formatMoney($invoice->amount, $invoice->client) }}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ $invoice->formatDate($invoice->date, $invoice->client->date_format()) }}
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white group hover:bg-gray-100">
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500" colspan="100%">
                            {{ ctrans('texts.no_results') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center md:justify-between mt-6 mb-6">
        @if($invoices->total() > 0)
            <span class="text-gray-700 text-sm hidden md:block">
                {{ ctrans('texts.showing_x_of', ['first' => $invoices->firstItem(), 'last' => $invoices->lastItem(), 'total' => $invoices->total()]) }}
            </span>
        @endif
        {{ $invoices->links('portal/ninja2020/vendor/pagination') }}
    </div>

</div>
