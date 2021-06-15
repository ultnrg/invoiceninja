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

namespace App\Http\Requests\Webhook;

use App\Http\Requests\Request;
use App\Utils\Traits\ChecksEntityStatus;
use App\Utils\Traits\MakesHash;

class UpdateWebhookRequest extends Request
{
    use MakesHash;
    use ChecksEntityStatus;

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
            'target_url' => 'url',
        ];
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        $this->replace($input);
    }
}
