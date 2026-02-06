<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    use Queueable;

    protected string $otpCode;

    public function __construct(string $otpCode)
    {
        $this->otpCode = $otpCode;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Login Verification Code')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your verification code is:')
            ->line('**' . $this->otpCode . '**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not attempt to log in, please ignore this email.');
    }
}
