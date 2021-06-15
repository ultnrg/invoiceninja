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

namespace App\Http\ValidationRules\Credit;

use App\Models\Credit;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class ValidCreditsRules.
 */
class ValidCreditsRules implements Rule
{
    use MakesHash;

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    private $error_msg;

    private $input;

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function passes($attribute, $value)
    {
        return $this->checkCreditsAreHomogenous();
    }

    private function checkCreditsAreHomogenous()
    {
        if (! array_key_exists('client_id', $this->input)) {

            $this->error_msg = ctrans('texts.client_id_required');

            return false;
        }

        $unique_array = [];

        foreach ($this->input['credits'] as $credit) {
            $unique_array[] = $credit['credit_id'];

            $cred = Credit::find($this->decodePrimaryKey($credit['credit_id']));

            if (! $cred) {
                $this->error_msg = ctrans('texts.credit_not_found');

                return false;
            }

            if ($cred->client_id != $this->input['client_id']) {
                $this->error_msg = ctrans('texts.invoices_dont_match_client');

                return false;
            }
        }

        if (! (array_unique($unique_array) == $unique_array)) {
            $this->error_msg = ctrans('texts.duplicate_credits_submitted');

            return false;
        }

        if (count($this->input['credits']) >= 1 && count($this->input['invoices']) == 0) {
            $this->error_msg = ctrans('texts.credit_with_no_invoice');

            return false;
        }

        if (count($this->input['credits']) >= 1) {

        }

        return true;
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->error_msg;
    }
}
