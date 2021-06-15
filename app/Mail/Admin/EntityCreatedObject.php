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

use App\Utils\Number;
use stdClass;

class EntityCreatedObject
{

    public $entity_type;

    public $entity;

    public $contact;

    public $company;

    public $settings;

    private $template_subject;

    private $template_body;

    public function __construct($entity, $entity_type)
    {
        $this->entity_type = $entity_type;
        $this->entity = $entity;
    }

    public function build()
    {

        $this->contact = $this->entity->invitations()->first()->contact;
        $this->company = $this->entity->company;

        $this->setTemplate();

        $mail_obj = new stdClass;
        $mail_obj->amount = $this->getAmount();
        $mail_obj->subject = $this->getSubject();
        $mail_obj->data = $this->getData();
        $mail_obj->markdown = 'email.admin.generic';
        $mail_obj->tag = $this->company->company_key;

        return $mail_obj;
    }

    private function setTemplate()
    {
        // nlog($this->template);

        switch ($this->entity_type) {
            case 'invoice':
                $this->template_subject = "texts.notification_invoice_created_subject";
                $this->template_body = "texts.notification_invoice_created_body";
                break;
            case 'quote':
                $this->template_subject = "texts.notification_quote_created_subject";
                $this->template_body = "texts.notification_quote_created_body";
                break;
            case 'credit':
                $this->template_subject = "texts.notification_credit_created_subject";
                $this->template_body = "texts.notification_credit_created_body";
                break;

            default:
                $this->template_subject = "texts.notification_invoice_created_subject";
                $this->template_body = "texts.notification_invoice_created_body";
                break;
        }
    }

    private function getAmount()
    {
        return Number::formatMoney($this->entity->amount, $this->entity->client);
    }

    private function getSubject()
    {
        return
            ctrans(
                $this->template_subject,
                [
                        'client' => $this->contact->present()->name(),
                        'invoice' => $this->entity->number,
                    ]
            );
    }

    private function getMessage()
    {
        return ctrans(
                $this->template_body,
                [
                    'amount' => $this->getAmount(),
                    'client' => $this->contact->present()->name(),
                    'invoice' => $this->entity->number,
                ]
            );
    }

    private function getData()
    {
        $settings = $this->entity->client->getMergedSettings();

        return [
            'title' => $this->getSubject(),
            'message' => $this->getMessage(),
            'url' => $this->entity->invitations()->first()->getAdminLink(),
            'button' => ctrans("texts.view_{$this->entity_type}"),
            'signature' => $settings->email_signature,
            'logo' => $this->company->present()->logo(),
            'settings' => $settings,
            'whitelabel' => $this->company->account->isPaid() ? true : false,
        ];
    }
}
