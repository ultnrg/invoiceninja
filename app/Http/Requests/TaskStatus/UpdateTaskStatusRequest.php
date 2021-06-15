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

namespace App\Http\Requests\TaskStatus;

use App\Http\Requests\Request;
use App\Utils\Traits\MakesHash;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends Request
{
    use MakesHash;

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
        $rules = [];

        if ($this->input('name')) {
            $rules['name'] = Rule::unique('task_statuses')->where('company_id', auth()->user()->company()->id)->ignore($this->task_status->id);
        }


        return $rules;
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

            if(array_key_exists('color', $input) && is_null($input['color']))
                $input['color'] = '#fff';

        $this->replace($input);
    }
}
