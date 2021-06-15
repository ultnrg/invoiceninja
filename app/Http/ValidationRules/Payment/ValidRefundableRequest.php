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

namespace App\Http\ValidationRules\Payment;

use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class ValidRefundableRequest.
 */
class ValidRefundableRequest implements Rule
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
        if (! array_key_exists('id', $this->input)) {
            $this->error_msg = ctrans('texts.payment_id_required');

            return false;
        }

        $payment = Payment::whereId($this->input['id'])->first();

        if (! $payment) {
            $this->error_msg = ctrans('texts.unable_to_retrieve_payment');

            return false;
        }

        $request_invoices = request()->has('invoices') ? $this->input['invoices'] : [];
        $request_credits = request()->has('credits') ? $this->input['credits'] : [];

        if ($payment->invoices()->exists()) {
            foreach ($payment->invoices as $paymentable_invoice) {
                $this->checkInvoice($paymentable_invoice, $request_invoices);
            }
        }


        foreach ($request_invoices as $request_invoice) {
            $this->checkInvoiceIsPaymentable($request_invoice, $payment);
        }

        if (strlen($this->error_msg) > 0) {
            return false;
        }

        return true;
    }

    private function checkInvoiceIsPaymentable($invoice, $payment)
    {
        $invoice = Invoice::whereId($invoice['invoice_id'])->whereCompanyId($payment->company_id)->first();

        if ($payment->invoices()->exists()) {
            $paymentable_invoice = $payment->invoices->where('id', $invoice->id)->first();

            if (! $paymentable_invoice) {
                $this->error_msg = ctrans('texts.invoice_not_related_to_payment', ['invoice' => $invoice->hashed_id]);

                return false;
            }
        } else {
            $this->error_msg = ctrans('texts.invoice_not_related_to_payment', ['invoice' => $invoice->hashed_id]);

            return false;
        }
    }

    private function checkCreditIsPaymentable($credit, $payment)
    {
        $credit = Credit::whereId($credit['credit_id'])->whereCompanyId($payment->company_id)->first();

        if ($payment->credits()->exists()) {
            $paymentable_credit = $payment->credits->where('id', $credit->id)->first();

            if (! $paymentable_invoice) {
                $this->error_msg = ctrans('texts.credit_not_related_to_payment', ['credit' => $credit->hashed_id]);

                return false;
            }
        } else {
            $this->error_msg = ctrans('texts.credit_not_related_to_payment', ['credit' => $credit->hashed_id]);

            return false;
        }
    }

    private function checkInvoice($paymentable, $request_invoices)
    {
        $record_found = false;

        foreach ($request_invoices as $request_invoice) {
            if ($request_invoice['invoice_id'] == $paymentable->pivot->paymentable_id) {
                $record_found = true;

                $refundable_amount = ($paymentable->pivot->amount - $paymentable->pivot->refunded);

                if ($request_invoice['amount'] > $refundable_amount) {
                    $invoice = $paymentable;

                    $this->error_msg = ctrans('texts.max_refundable_invoice', ['invoice' => $invoice->hashed_id, 'amount' => $refundable_amount]);

                    return false;
                }
            }
        }

        if (! $record_found) {
            $this->error_msg = ctrans('texts.refund_without_invoices');

            return false;
        }
    }

    private function checkCredit($paymentable, $request_credits)
    {
        $record_found = null;

        foreach ($request_credits as $request_credit) {
            if ($request_credit['credit_id'] == $paymentable->pivot->paymentable_id) {
                $record_found = true;

                $refundable_amount = ($paymentable->pivot->amount - $paymentable->pivot->refunded);

                if ($request_credit['amount'] > $refundable_amount) {
                    $credit = $paymentable;

                    $this->error_msg = ctrans('texts.max_refundable_credit',['credit' => $credit->hashed_id, 'amount' => $refundable_amount]);

                    return false;
                }
            }
        }

        if (! $record_found) {
            $this->error_msg = ctrans('texts.refund_without_credits');

            return false;
        }
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->error_msg;
    }
}
