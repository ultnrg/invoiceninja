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

namespace App\Http\Requests\TaxRate;

use App\Http\Requests\Request;

class StoreTaxRateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules()
    {
        return [
            //'name' => 'required',
            'name' => 'required|unique:tax_rates,name,null,null,company_id,'.auth()->user()->companyId(),
            'rate' => 'required|numeric',
        ];
    }
}
