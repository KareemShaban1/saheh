<?php

namespace App\Events;

use App\Models\TempData;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationRegistration
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tempData;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TempData $tempData)
    {
        //
        $this->tempData = $tempData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
