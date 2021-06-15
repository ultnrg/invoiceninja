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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHash extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'object',
    ];

    
    public function invoices()
    {
        return $this->data->invoices;
    }

    public function credits_total()
    {
        return isset($this->data->credits) ? $this->data->credits : 0;
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }

    public function fee_invoice()
    {
        return $this->belongsTo(Invoice::class, 'fee_invoice_id', 'id');
    }
}
