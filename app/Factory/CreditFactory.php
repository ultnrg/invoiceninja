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

use App\Models\Client;
use App\Models\Credit;

class CreditFactory
{
    public static function create(int $company_id, int $user_id, object $settings = null, Client $client = null) :Credit
    {
        $credit = new Credit();
        $credit->status_id = Credit::STATUS_DRAFT;
        $credit->number = null;
        $credit->discount = 0;
        $credit->is_amount_discount = true;
        $credit->po_number = '';
        $credit->footer = '';
        $credit->terms = '';
        $credit->public_notes = '';
        $credit->private_notes = '';
        $credit->date = null;
        $credit->due_date = null;
        $credit->partial_due_date = null;
        $credit->is_deleted = false;
        $credit->line_items = json_encode([]);
        $credit->tax_name1 = '';
        $credit->tax_rate1 = 0;
        $credit->tax_name2 = '';
        $credit->tax_rate2 = 0;
        $credit->custom_value1 = '';
        $credit->custom_value2 = '';
        $credit->custom_value3 = '';
        $credit->custom_value4 = '';
        $credit->amount = 0;
        $credit->balance = 0;
        $credit->partial = 0;
        $credit->user_id = $user_id;
        $credit->company_id = $company_id;
        $credit->recurring_id = null;

        return $credit;
    }
}
