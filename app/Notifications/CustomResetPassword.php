<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public function toMail($notifiable)
    {
        // Customize the frontend link
        $resetUrl = config('app.frontend_url') .
            '/reset.html?token=' . $this->token .
            '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('Click the button below to reset your password.')
            ->action('Reset Password', $resetUrl)
            ->line('If you didn’t request a password reset, no further action is needed.');
    }
}


