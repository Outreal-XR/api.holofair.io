<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MetaverseInviteNotification extends Notification
{
    use Queueable;
    protected $sender;
    protected $metaverse;
    protected $invite;
    protected $type;
    protected $title;

    /**
     * Create a new notification instance.
     */
    public function __construct($sender, $metaverse, $invite)
    {
        $this->sender = $sender;
        $this->metaverse = $metaverse;
        $this->invite = $invite;
        $this->type = 'metaverse_invite';
        $this->title = 'World Join Invitation';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender' => $this->sender,
            'message' => 'You have been invited to join the world ' . $this->metaverse->name . ' by ' . $this->sender->fullName() . ' as a ' . $this->invite->role . '.',
            'path' => 'metaverses/' . $this->metaverse->id . '/invitations/' . $this->invite->id,
            'type' => $this->type,
            'title' => $this->title
        ];
    }
}
