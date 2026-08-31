<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GmailService
{
    protected $client;

    protected $service;

    public function __construct()
    {
        $credentialsPath = storage_path('app/google/credentials.json');
        $tokenPath = storage_path('app/google/tokens.json');

        if (! file_exists($credentialsPath) || ! file_exists($tokenPath)) {
            // Google OAuth token files not available, will use Laravel Mail (SMTP) fallback
            return;
        }

        try {
            $this->client = new Client;
            $this->client->setAuthConfig($credentialsPath);

            $tokens = json_decode((string) file_get_contents($tokenPath), true);
            if (! is_array($tokens) || $tokens === []) {
                return;
            }

            $this->client->setAccessToken($tokens);

            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken() ?: ($tokens['refresh_token'] ?? null);
                if (! $refreshToken) {
                    return;
                }

                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (is_array($newToken) && isset($newToken['error'])) {
                    return;
                }

                if (! isset($newToken['refresh_token'])) {
                    $newToken['refresh_token'] = $refreshToken;
                }

                file_put_contents($tokenPath, json_encode($newToken, JSON_UNESCAPED_UNICODE));
                $this->client->setAccessToken($newToken);
            }

            $this->service = new Gmail($this->client);
        } catch (\Throwable $e) {
            Log::warning('GmailService OAuth init failed, will use SMTP fallback: '.$e->getMessage());
            $this->service = null;
        }
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if ($this->service) {
            try {
                $rawMessage = "To: $to\r\n".
                              'Subject: =?utf-8?B?'.base64_encode($subject)."?=\r\n".
                              "MIME-Version: 1.0\r\n".
                              "Content-Type: text/html; charset=utf-8\r\n\r\n".
                              $htmlBody;

                $raw = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

                $message = new Message;
                $message->setRaw($raw);

                $this->service->users_messages->send('me', $message);

                Log::info("Email sent to {$to} via Gmail API");

                return true;
            } catch (\Throwable $e) {
                Log::warning('Gmail API send failed, falling back to SMTP: '.$e->getMessage());
            }
        }

        // Fallback to Laravel Mail (SMTP / configured mailer)
        try {
            Mail::html($htmlBody, function ($msg) use ($to, $subject) {
                $msg->to($to)
                    ->subject($subject);
            });

            Log::info("Email sent to {$to} via Laravel Mail");

            return true;
        } catch (\Throwable $e) {
            Log::error('Laravel Mail send failed: '.$e->getMessage());
            throw new \RuntimeException('Failed to send email: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Alias kept so controllers that call sendMail() still work.
     */
    public function sendMail(string $to, string $subject, string $htmlBody): bool
    {
        return $this->send($to, $subject, $htmlBody);
    }
}
