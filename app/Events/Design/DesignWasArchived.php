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

namespace App\Events\Design;

use App\Models\Company;
use App\Models\Design;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class DesignWasArchived.
 */
class DesignWasArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Design
     */
    public $design;

    public $company;

    public $event_vars;

    /**
     * Create a new event instance.
     *
     * @param Design $design
     * @param Company $company
     * @param array $event_vars
     */
    public function __construct(Design $design, Company $company, array $event_vars)
    {
        $this->design = $design;

        $this->company = $company;

        $this->event_vars = $event_vars;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
