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

namespace App\Events\Quote;

use App\Models\Company;
use App\Models\Quote;
use App\Models\QuoteInvitation;
use Illuminate\Queue\SerializesModels;

/**
 * Class QuoteWasEmailed.
 */
class QuoteWasEmailed
{
    use SerializesModels;

    public $invitation;

    public $company;

    public $event_vars;

    public $template;
    /**
     * Create a new event instance.
     *
     * @param Quote $quote
     * @param string $notes
     * @param Company $company
     * @param array $event_vars
     */
    public function __construct(QuoteInvitation $invitation, Company $company, array $event_vars, string $template)
    {
        $this->invitation = $invitation;
        $this->company = $company;
        $this->event_vars = $event_vars;
        $this->template = $template;
    }
}
