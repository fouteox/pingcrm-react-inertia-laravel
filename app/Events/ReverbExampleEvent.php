<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

final class ReverbExampleEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private readonly string $locale;

    public function __construct(
        private readonly string $uuid,
        ?string $locale = null
    ) {
        $this->locale = $locale ?? App::getLocale();
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('reverb.'.$this->uuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'reverb.completed';
    }

    /**
     * @return array{type: string, status: string, message: string}
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'reverb',
            'status' => 'completed',
            'message' => __('Example of reverb notification', [], $this->locale),
        ];
    }
}
