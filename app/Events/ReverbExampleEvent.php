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

    private string $message;

    private string $locale;

    public function __construct(
        private readonly string $uuid,
        ?string $locale = null
    ) {
        $this->locale = $locale ?? App::getLocale();
    }

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

    public function broadcastWith(): array
    {
        App::setLocale($this->locale);

        return [
            'type' => 'reverb',
            'status' => 'completed',
            'message' => __('Example of reverb notification'),
        ];
    }
}
