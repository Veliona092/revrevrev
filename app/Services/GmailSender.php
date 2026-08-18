<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GmailSender
{
    protected $service;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));

        // Load saved tokens (for one account – for multi: load per account)
        $tokens = json_decode(file_get_contents(storage_path('app/google/tokens.json')), true);
        $client->setAccessToken($tokens);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            // Save new tokens back
            file_put_contents(storage_path('app/google/tokens.json'), json_encode($client->getAccessToken()));
        }

        $this->service = new Gmail($client);
    }

    public function sendVerification($to, $url)
    {
        $raw = "To: $to\r\n" .
               "Subject: Verify Your Reviso Account\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=utf-8\r\n\r\n" .
               "<h1>Welcome to Reviso!</h1>" .
               "<p>Click here to verify: <a href='$url'>Verify Email</a></p>" .
               "<p>Thanks,<br>Reviso Team</p>";

        $raw = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $message = new Message();
        $message->setRaw($raw);

        $this->service->users_messages->send('me', $message);
    }
}