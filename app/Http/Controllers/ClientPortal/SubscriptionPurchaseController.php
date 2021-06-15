<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPurchaseController extends Controller
{
    public function index(Subscription $subscription, Request $request)
    {
        if ($request->has('locale')) {
            $this->setLocale($request->query('locale'));
        }

        return view('billing-portal.purchase', [
            'subscription' => $subscription,
            'hash' => Str::uuid()->toString(),
            'request_data' => $request->all(),
        ]);
    }

    /**
     * Set locale for incoming request.
     *
     * @param string $locale
     */
    private function setLocale(string $locale): void
    {
        $record = Cache::get('languages')->filter(function ($item) use ($locale) {
            return $item->locale == $locale;
        })->first();

        if ($record) {
            App::setLocale($record->locale);
        }
    }
}
