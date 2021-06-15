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

namespace App\Jobs\Util;

use App\Exceptions\MigrationValidatorFailed;
use App\Exceptions\NonExistingMigrationFile;
use App\Exceptions\ProcessingMigrationArchiveFailed;
use App\Exceptions\ResourceDependencyMissing;
use App\Exceptions\ResourceNotAvailableForMigration;
use App\Libraries\MultiDB;
use App\Mail\MigrationFailed;
use App\Models\Company;
use App\Models\User;
use App\Utils\Ninja;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class StartMigration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $filepath;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Company
     */
    private $company;

    /**
     * Create a new job instance.
     *
     * @param $filepath
     * @param User $user
     * @param Company $company
     */
    public $tries = 1;

    public $timeout = 0;

    //  public $maxExceptions = 2;

    //public $backoff = 86430;

    public function __construct($filepath, User $user, Company $company)
    {
        $this->filepath = $filepath;
        $this->user = $user;
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        nlog("Inside Migration Job");
        
        set_time_limit(0);

        MultiDB::setDb($this->company->db);

        auth()->login($this->user, false);

        auth()->user()->setCompany($this->company);

        $this->company->is_disabled = true;
        $this->company->save();

        $zip = new ZipArchive();
        $archive = $zip->open(public_path("storage/{$this->filepath}"));
        $filename = pathinfo($this->filepath, PATHINFO_FILENAME);

            // if($this->company->id == $this->company->account->default_company_id)
            // {
            //     $new_default_company = $this->company->account->companies->first();

            //     if ($new_default_company) {
            //         $this->company->account->default_company_id = $new_default_company->id;
            //         $this->company->account->save();
            //     }
            // }

        $update_product_flag = $this->company->update_products;

        $this->company->update_products = false;
        $this->company->save();

        try {
            if (! $archive) {
                throw new ProcessingMigrationArchiveFailed('Processing migration archive failed. Migration file is possibly corrupted.');
            }

            $zip->extractTo(public_path("storage/migrations/{$filename}"));
            $zip->close();

            if (app()->environment() == 'testing') {
                return true;
            }

            $file = public_path("storage/migrations/$filename/migration.json");

            if (! file_exists($file)) {
                throw new NonExistingMigrationFile('Migration file does not exist, or it is corrupted.');
            }

            Import::dispatchNow($file, $this->company, $this->user);

            Storage::deleteDirectory(public_path("storage/migrations/{$filename}"));

            // $this->company->account->default_company_id = $this->company->id;
            // $this->company->account->save();

            $this->company->update_products = $update_product_flag;
            $this->company->save();

        } catch (NonExistingMigrationFile | ProcessingMigrationArchiveFailed | ResourceNotAvailableForMigration | MigrationValidatorFailed | ResourceDependencyMissing | \Exception $e) {

            $this->company->update_products = $update_product_flag;
            $this->company->save();


            if(Ninja::isHosted())
                app('sentry')->captureException($e);
            
            Mail::to($this->user->email, $this->user->name())->send(new MigrationFailed($e, $this->company, $e->getMessage()));

            if (app()->environment() !== 'production') {
                info($e->getMessage());
            }
            
        }

        //always make sure we unset the migration as running

        return true;
    }

    public function failed($exception = null)
    {
        info(print_r($exception->getMessage(), 1));
    }
}
