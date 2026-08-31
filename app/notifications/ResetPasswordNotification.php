<?php

namespace App\Notifications;

use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail']; // still use 'mail' channel
    }

    public function toMail($notifiable)
    {
        Log::info('ResetPasswordNotification::toMail called for: '.$notifiable->email);

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $html = '<h1>Reset Your Reviso Password</h1>'.
                '<p>Hello,</p>'.
                '<p>You requested to reset your password. Click the button below to set a new one:</p>'.
                "<a href='{$url}' style='background:#5e72e4;color:white;padding:12px 24px;text-decoration:none;border-radius:4px;'>Reset Password</a>".
                '<p>This link will expire in 60 minutes.</p>'.
                '<p>If you did not request this, no action is needed.</p>'.
                '<p>Thanks,<br>Reviso Team</p>';

        try {
            Log::info('Attempting Gmail API reset send to: '.$notifiable->email.' | URL: '.$url);

            $gmailService = new GmailService;
            $gmailService->send($notifiable->email, 'Reset Your Reviso Password', $html);

            Log::info('Gmail API reset send completed successfully for: '.$notifiable->email);
        } catch (\Exception $e) {
            Log::error('Gmail API reset send FAILED: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());
        }

        // Return proper message so Laravel shows success
        return (new MailMessage)
            ->subject('Reset Your Reviso Password')
            ->line('Reset link sent via Gmail API.');
    }
}
