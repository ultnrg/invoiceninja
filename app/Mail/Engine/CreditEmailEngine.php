<?php
/**
 * Credit Ninja (https://creditninja.com).
 *
 * @link https://github.com/creditninja/creditninja source repository
 *
 * @copyright Copyright (c) 2021. Credit Ninja LLC (https://creditninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Mail\Engine;

use App\Utils\HtmlEngine;
use App\Utils\Ninja;
use App\Utils\Number;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

class CreditEmailEngine extends BaseEmailEngine
{
    public $invitation;

    public $client;

    public $credit;

    public $contact;

    public $reminder_template;

    public $template_data;

    public function __construct($invitation, $reminder_template, $template_data)
    {
        $this->invitation = $invitation;
        $this->reminder_template = $reminder_template;
        $this->client = $invitation->contact->client;
        $this->credit = $invitation->credit;
        $this->contact = $invitation->contact;
        $this->template_data = $template_data;
    }

    public function build()
    {
        App::forgetInstance('translator');
        $t = app('translator');
        $t->replace(Ninja::transformTranslations($this->client->getMergedSettings()));
        
        if($this->reminder_template == 'endless_reminder')
            $this->reminder_template = 'reminder_endless';
        
        if (is_array($this->template_data) &&  array_key_exists('body', $this->template_data) && strlen($this->template_data['body']) > 0) {
            $body_template = $this->template_data['body'];
        } else {
            $body_template = $this->client->getSetting('email_template_'.$this->reminder_template);
        }

        /* Use default translations if a custom message has not been set*/
        if (iconv_strlen($body_template) == 0) {
            $body_template = trans(
                'texts.credit_message',
                [
                    'credit' => $this->credit->number,
                    'company' => $this->credit->company->present()->name(),
                    'amount' => Number::formatMoney($this->credit->balance, $this->client),
                ],
                null,
                $this->client->locale()
            );
        }

        if (is_array($this->template_data) &&  array_key_exists('subject', $this->template_data) && strlen($this->template_data['subject']) > 0) {
            $subject_template = $this->template_data['subject'];
        } else {
            $subject_template = $this->client->getSetting('email_subject_'.$this->reminder_template);
        }

        if (iconv_strlen($subject_template) == 0) {
            $subject_template = trans(
                'texts.credit_subject',
                [
                    'number' => $this->credit->number,
                    'account' => $this->credit->company->present()->name(),
                ],
                null,
                $this->client->locale()
            );
        }

        $this->setTemplate($this->client->getSetting('email_style'))
            ->setContact($this->contact)
            ->setVariables((new HtmlEngine($this->invitation))->makeValues())//move make values into the htmlengine
            ->setSubject($subject_template)
            ->setBody($body_template)
            ->setFooter("<a href='{$this->invitation->getLink()}'>".ctrans('texts.view_credit').'</a>')
            ->setViewLink($this->invitation->getLink())
            ->setViewText(ctrans('texts.view_credit'))
            ->setInvitation($this->invitation);

        if ($this->client->getSetting('pdf_email_attachment') !== false && $this->credit->company->account->hasFeature(Account::FEATURE_PDF_ATTACHMENT)) {

            if(Ninja::isHosted())
                $this->setAttachments([$this->credit->pdf_file_path($this->invitation, 'url', true)]);
            else
                $this->setAttachments([$this->credit->pdf_file_path($this->invitation)]);
            
        }

        //attach third party documents
        if($this->client->getSetting('document_email_attachment') !== false && $this->credit->company->account->hasFeature(Account::FEATURE_DOCUMENTS)){

            // Storage::url
            foreach($this->credit->documents as $document){
                $this->setAttachments([['path' => $document->filePath(), 'name' => $document->name, 'mime' => $document->type]]);
            }

            foreach($this->credit->company->documents as $document){
                $this->setAttachments([['path' => $document->filePath(), 'name' => $document->name, 'mime' => $document->type]]);
            }

        }

        return $this;
    }
}
