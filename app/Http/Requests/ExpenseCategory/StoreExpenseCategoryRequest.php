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

namespace App\Http\Requests\ExpenseCategory;

use App\Http\Requests\Request;
use App\Models\ExpenseCategory;

class StoreExpenseCategoryRequest extends Request
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return auth()->user()->can('create', ExpenseCategory::class);
    }

    public function rules()
    {
        $rules = [];

        $rules['name'] = 'required|unique:expense_categories,name,null,null,company_id,'.auth()->user()->companyId();

        return $this->globalRules($rules);
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        $input = $this->decodePrimaryKeys($input);

        if(array_key_exists('color', $input) && is_null($input['color']))
            $input['color'] = '#fff';

        $this->replace($input);
    }
}