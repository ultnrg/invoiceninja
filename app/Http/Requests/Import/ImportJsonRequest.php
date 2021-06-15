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

namespace App\Http\Requests\Import;

use App\Http\Requests\Request;

class ImportJsonRequest extends Request
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
			// 'import_type' => 'required',
   //          'files' => 'required_without:hash|array|min:1|max:6',
   //          'hash' => 'nullable|string',
   //          'column_map' => 'required_with:hash|array',
   //          'skip_header' => 'required_with:hash|boolean',
			// 'files.*' => 'file|mimes:csv,txt',
        ];
    }
}
