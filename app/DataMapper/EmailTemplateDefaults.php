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

namespace App\DataMapper;

use Illuminate\Support\Facades\App;

class EmailTemplateDefaults
{
    public static function getDefaultTemplate($template, $locale)
    {
        App::setLocale($locale);

        switch ($template) {

            /* Template */

            case 'email_template_invoice':
                return self::emailInvoiceTemplate();
                break;
            case 'email_template_quote':
                return self::emailQuoteTemplate();
                break;
            case 'email_template_credit':
                return self::emailCreditTemplate();
                break;
            case 'email_template_payment':
                return self::emailPaymentTemplate();
                break;
            case 'email_template_payment_partial':
                return self::emailPaymentPartialTemplate();
                break;
            case 'email_template_statement':
                return self::emailStatementTemplate();
                break;
            case 'email_template_reminder1':
                return self::emailReminder1Template();
                break;
            case 'email_template_reminder2':
                return self::emailReminder2Template();
                break;
            case 'email_template_reminder3':
                return self::emailReminder3Template();
                break;
            case 'email_template_reminder_endless':
                return self::emailReminderEndlessTemplate();
                break;
            case 'email_template_custom1':
                return self::emailInvoiceTemplate();
                break;
            case 'email_template_custom2':
                return self::emailInvoiceTemplate();
                break;
            case 'email_template_custom3':
                return self::emailInvoiceTemplate();
                break;

            /* Subject */

            case 'email_subject_invoice':
                return self::emailInvoiceSubject();
                break;
            case 'email_subject_quote':
                return self::emailQuoteSubject();
                break;
            case 'email_subject_credit':
                return self::emailCreditSubject();
                break;
            case 'email_subject_payment':
                return self::emailPaymentSubject();
                break;
            case 'email_subject_payment_partial':
                return self::emailPaymentPartialSubject();
                break;
            case 'email_subject_statement':
                return self::emailStatementSubject();
                break;
            case 'email_subject_reminder1':
                return self::emailReminder1Subject();
                break;
            case 'email_subject_reminder2':
                return self::emailReminder2Subject();
                break;
            case 'email_subject_reminder3':
                return self::emailReminder3Subject();
                break;
            case 'email_subject_reminder_endless':
                return self::emailReminderEndlessSubject();
                break;
            case 'email_subject_custom1':
                return self::emailInvoiceSubject();
                break;
            case 'email_subject_custom2':
                return self::emailInvoiceSubject();
                break;
            case 'email_subject_custom3':
                return self::emailInvoiceSubject();
                break;

            default:
                return self::emailInvoiceTemplate();
                break;
        }
    }

    public static function emailInvoiceSubject()
    {
        return ctrans('texts.invoice_subject', ['number'=>'$number', 'account'=>'$company.name']);
    }

    public static function emailCreditSubject()
    {
        return ctrans('texts.credit_subject', ['number'=>'$number', 'account'=>'$company.name']);
    }

    public static function emailInvoiceTemplate()
    {
        $invoice_message = '<p>'.self::transformText('invoice_message').'</p><div class="center">$view_link</div>';

        return $invoice_message;
    }

    public static function emailQuoteSubject()
    {
        return ctrans('texts.quote_subject', ['number'=>'$number', 'account'=>'$company.name']);
    }

    public static function emailQuoteTemplate()
    {
        $quote_message = '<p>'.self::transformText('quote_message').'</p><div class="center">$view_link</div>';

        return $quote_message;
    }

    public static function emailPaymentSubject()
    {
        return ctrans('texts.payment_subject');
    }

    public static function emailPaymentTemplate()
    {
        $payment_message = '<p>'.self::transformText('payment_message').'</p><div class="center">$view_link</div>';

        return $payment_message;
    }

    public static function emailCreditTemplate()
    {
        $credit_message = '<p>'.self::transformText('credit_message').'</p><div class="center">$view_link</div>';

        return $credit_message;
    }

    public static function emailPaymentPartialTemplate()
    {
        $payment_message = '<p>'.self::transformText('payment_message').'</p><div class="center">$view_link</div>';

        return $payment_message;
    }

    public static function emailPaymentPartialSubject()
    {
        return ctrans('texts.payment_subject');
    }

    public static function emailReminder1Subject()
    {
        return ctrans('texts.reminder_subject', ['invoice'=>'$invoice.number', 'account'=>'$company.name']);
    }

    public static function emailReminder1Template()
    {
        return '';
    }

    public static function emailReminder2Subject()
    {
        return ctrans('texts.reminder_subject', ['invoice'=>'$invoice.number', 'account'=>'$company.name']);
    }

    public static function emailReminder2Template()
    {
        return '';
    }

    public static function emailReminder3Subject()
    {
        return ctrans('texts.reminder_subject', ['invoice'=>'$invoice.number', 'account'=>'$company.name']);
    }

    public static function emailReminder3Template()
    {
        return '';
    }

    public static function emailReminderEndlessSubject()
    {
        return ctrans('texts.reminder_subject', ['invoice'=>'$invoice.number', 'account'=>'$company.name']);
    }

    public static function emailReminderEndlessTemplate()
    {
        return '';
    }

    public static function emailStatementSubject()
    {
        return '';
    }

    public static function emailStatementTemplate()
    {
        return '';
    }

    private static function transformText($string)
    {
        //preformat the string, removing trailing colons.

        return str_replace(':', '$', rtrim( ctrans('texts.'.$string), ":"));
    }
}
