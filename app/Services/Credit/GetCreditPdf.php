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

namespace App\Services\Credit;

use App\Jobs\Entity\CreateEntityPdf;
use App\Services\AbstractService;
use App\Utils\TempFile;
use Illuminate\Support\Facades\Storage;

class GetCreditPdf extends AbstractService
{
    public $credit;

    public $contact;

    public $invitation;

    public function __construct($invitation)
    {
        $this->invitation = $invitation;
        $this->credit = $invitation->credit;
        $this->contact = $invitation->contact;
    }

    public function run()
    {
        if (! $this->contact) {
            $this->contact = $this->credit->client->primary_contact()->first();
        }

        $path = $this->credit->client->credit_filepath($this->invitation);

        $file_path = $path.$this->credit->numberFormatter().'.pdf';

        $disk = 'public';

        $file_path = CreateEntityPdf::dispatchNow($this->invitation);

        return Storage::disk($disk)->path($file_path);
    }
}
