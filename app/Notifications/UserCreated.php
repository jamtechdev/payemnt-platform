<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserCreated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $password,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your admin account has been created')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An admin account has been created for you on the PartnerSales portal.')
            ->line('You can sign in using the credentials below:')
            ->line('**Email:** ' . $notifiable->email)
            ->line('**Password:** ' . $this->password)
            ->action('Sign in', route('login'))
            ->line('For security, please change your password after your first login.');
    }
}
