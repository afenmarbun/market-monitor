<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

final class QuotesUpdated implements ShouldBroadcastNow, ShouldRescue
{
    public function __construct(private readonly array $payload) {}

    public function broadcastOn(): array
    {
        return [new Channel('market')];
    }

    public function broadcastAs(): string
    {
        return 'quotes.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
