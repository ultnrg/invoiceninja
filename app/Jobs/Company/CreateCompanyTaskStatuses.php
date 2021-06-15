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

namespace App\Jobs\Company;

use App\Libraries\MultiDB;
use App\Models\TaskStatus;
use App\Utils\Traits\MakesHash;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateCompanyTaskStatuses
{
    use MakesHash;
    use Dispatchable;

    protected $company;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @param $company
     * @param $user
     */
    public function __construct($company, $user)
    {
        $this->company = $company;

        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        MultiDB::setDb($this->company->db);
        
        $task_statuses = [
            ['name' => ctrans('texts.backlog'), 'company_id' => $this->company->id, 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now(), 'status_order' => 1],
            ['name' => ctrans('texts.ready_to_do'), 'company_id' => $this->company->id, 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now(), 'status_order' => 2],
            ['name' => ctrans('texts.in_progress'), 'company_id' => $this->company->id, 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now(), 'status_order' => 3],
            ['name' => ctrans('texts.done'), 'company_id' => $this->company->id, 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now(), 'status_order' => 4],

        ];

        TaskStatus::insert($task_statuses);
    }
}
