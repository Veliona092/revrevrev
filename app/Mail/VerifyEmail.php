<?php

namespace App\Mail;

use App\Models\Signup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $signup;
    public $url;

    public function __construct(Signup $signup, string $url)
    {
        $this->signup = $signup;
        $this->url = $url;
    }

public function build()
{
    return $this->subject('Verify Your Reviso Account')
                ->text('emails.verify-text')     // ← this line must match the file name
                ->with(['url' => $this->url]);
}
}