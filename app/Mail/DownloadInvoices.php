<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DownloadInvoices extends Mailable
{
    // use Queueable, SerializesModels;

    public $file_path;

    public $company;

    public function __construct($file_path, Company $company)
    {
        $this->file_path = $file_path;

        $this->company = $company;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject(ctrans('texts.download_files'))
                    ->markdown(
                        'email.admin.download_files',
                        [
                            'url' => $this->file_path,
                            'logo' => $this->company->present()->logo,
                            'whitelabel' => $this->company->account->isPaid() ? true : false,
                        ]
                    );
    }
}
