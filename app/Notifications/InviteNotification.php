<?php

namespace App\Notifications;

use App\Models\Metaverse;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class InviteNotification extends Notification
{
    use Queueable;
    protected string $message;
    protected Metaverse $metaverse;
    protected string $url;
    protected string $role;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message, Metaverse $metaverse, string $role = 'viewer')
    {
        $this->message = $message;
        $this->metaverse = $metaverse;
        $this->role = $role;
        $this->url = '/metaverses/' . $this->metaverse->id;

        $this->afterCommit();
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
            'sender' => [
                'id' => Auth::id(),
                'name' => Auth::user()->fullName(),
                'avatar' => Auth::user()->avatar ? asset(Auth::user()->avatar) : null,
            ],
            'message' => $this->message,
            'url' => $this->url,
            'metaverse' => [
                'id' => $this->metaverse->id,
                'name' => $this->metaverse->name,
                'thumbnail' => $this->metaverse->thumbnail ? asset($this->metaverse->thumbnail) : null,
                'creator' => [
                    'id' => $this->metaverse->user->id,
                    'name' => $this->metaverse->user->fullName(),
                    'avatar' => $this->metaverse->user->avatar ? asset($this->metaverse->user->avatar) : null,
                ]
            ],
            'role' => $this->role,
        ];
    }
}
