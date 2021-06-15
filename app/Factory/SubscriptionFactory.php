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

namespace App\Factory;

use App\Models\Subscription;

class SubscriptionFactory
{
    public static function create(int $company_id, int $user_id): Subscription
    {
        $billing_subscription = new Subscription();
        $billing_subscription->company_id = $company_id;
        $billing_subscription->user_id = $user_id;

        return $billing_subscription;
    }
}
