<?php

namespace App\Services;

use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;

class GmailService
{
    protected $client;

    protected $service;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));

        $tokenPath = storage_path('app/google/tokens.json');
        $tokens = file_exists($tokenPath)
            ? json_decode((string) file_get_contents($tokenPath), true)
            : null;

        if (! is_array($tokens) || $tokens === []) {
            throw new \RuntimeException('Gmail is not connected. Missing tokens.');
        }

        $this->client->setAccessToken($tokens);

        // Refresh if expired
        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken() ?: ($tokens['refresh_token'] ?? null);

            if (! $refreshToken) {
                throw new \RuntimeException('Gmail token expired and no refresh token is available.');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (is_array($newToken) && isset($newToken['error'])) {
                throw new \RuntimeException('Gmail authorization has expired or was revoked. Please reconnect Gmail.');
            }

            if (! isset($newToken['refresh_token'])) {
                $newToken['refresh_token'] = $refreshToken;
            }

            // Save updated tokens
            file_put_contents($tokenPath, json_encode($newToken, JSON_UNESCAPED_UNICODE));
            $this->client->setAccessToken($newToken);
        }

        $this->service = new Gmail($this->client);
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
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
        } catch (GoogleServiceException $e) {
            $body = (string) $e->getMessage();
            if (str_contains($body, 'invalid_grant')) {
                Log::error('Gmail API token invalid_grant: '.$body);
                throw new \RuntimeException('Gmail authorization expired or revoked. Please reconnect Gmail.');
            }

            Log::error('Gmail API send failed: '.$body);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Gmail API send failed: '.$e->getMessage());
            throw $e;
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
