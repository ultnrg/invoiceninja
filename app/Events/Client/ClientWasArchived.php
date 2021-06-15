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

namespace App\Events\Client;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ClientWasArchived.
 */
class ClientWasArchived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Client
     */
    public $client;

    public $company;

    public $event_vars;

    /**
     * Create a new event instance.
     *
     * @param Client $client
     * @param Company $company
     * @param array $event_vars
     */
    public function __construct(Client $client, Company $company, array $event_vars)
    {
        $this->client = $client;
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
