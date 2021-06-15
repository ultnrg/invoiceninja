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

namespace App\Mail\Admin;

use App\Models\Invoice;
use App\Utils\HtmlEngine;
use App\Utils\Number;
use App\Utils\Traits\MakesHash;
use stdClass;

class ClientPaymentFailureObject
{
     use MakesHash;

    public $client;

    public $error;

    public $company;

    public $payment_hash;

    private $invoices;

    /**
     * Create a new job instance.
     *
     * @param $client
     * @param $message
     * @param $company
     * @param $amount
     */
    public function __construct($client, $error, $company, $payment_hash)
    {
        $this->client = $client;

        $this->error = $error;

        $this->company = $company;

        $this->payment_hash = $payment_hash;

        $this->company = $company;

    }

    public function build()
    {

        $this->invoices = Invoice::whereIn('id', $this->transformKeys(array_column($this->payment_hash->invoices(), 'invoice_id')))->get();

        $mail_obj = new stdClass;
        $mail_obj->amount = $this->getAmount();
        $mail_obj->subject = $this->getSubject();
        $mail_obj->data = $this->getData();
        $mail_obj->markdown = 'email.admin.generic';
        $mail_obj->tag = $this->company->company_key;

        return $mail_obj;
    }

    private function getAmount()
    {

       return array_sum(array_column($this->payment_hash->invoices(), 'amount')) + $this->payment_hash->fee_total;

    }

    private function getSubject()
    {

        return
            ctrans(
                'texts.notification_invoice_payment_failed_subject',
                ['invoice' => $this->client->present()->name()]
            );

    }

    private function getData()
    {
        $signature = $this->client->getSetting('email_signature');
        $html_variables = (new HtmlEngine($this->invoices->first()->invitations->first()))->makeValues();
        $signature = str_replace(array_keys($html_variables), array_values($html_variables), $signature);

        $data = [
            'title' => ctrans(
                'texts.notification_invoice_payment_failed_subject',
                [
                    'invoice' => $this->invoices->first()->number
                ]
            ),
            'greeting' => ctrans('texts.email_salutation', ['name' => $this->client->present()->name]),
            'message' => $this->error,
            'signature' => $signature,
            'logo' => $this->company->present()->logo(),
            'settings' => $this->client->getMergedSettings(),
            'whitelabel' => $this->company->account->isPaid() ? true : false,
            'url' => route('client.login'),
            'button' => ctrans('texts.login'),
            'additional_info' => false
        ];

        return $data;
    }


}
