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

namespace App\Mail\Migration;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MaxCompanies extends Mailable
{
    // use Queueable, SerializesModels;

    public $company;

    public $settings;

    public $logo;

    public $title;

    public $message;

    public $whitelabel;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($company)
    {
        $this->company = $company;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->settings = $this->company->settings;
        $this->logo = $this->company->present()->logo();
        $this->title = ctrans('texts.max_companies');
        $this->message = ctrans('texts.max_companies_desc');
        $this->whitelabel = $this->company->account->isPaid();

        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject(ctrans('texts.max_companies'))
                    ->view('email.migration.max_companies');
    }
}
