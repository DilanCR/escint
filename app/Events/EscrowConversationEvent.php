<?php

namespace App\Events;

use App\Models\Escrow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscrowConversationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $escrow;
    public $conversation;
    public $senderImage;
    public $attachments; 
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Escrow $escrow, $conversation)
    {
        $this->escrow = $escrow;
        $this->conversation = $conversation;
        $this->senderImage = $conversation->senderImage;
        $this->attachments = $conversation->conversationsAttachments;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    { 
        return ["support.conversation.".$this->escrow->id];
    }
    public function broadcastAs()
    {
        return 'escrow-conversation';
    }
}
