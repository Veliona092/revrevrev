<?php
// app/Http/Controllers/TestAiController.php

namespace App\Http\Controllers;

use App\Services\CloudflareAI;
use Illuminate\Http\Request;

class TestAiController extends Controller
{
    public function test(CloudflareAI $ai, Request $request)
    {
        // Build the messages array. The 'user' content is a JSON string payload.
        $messages = [
            ['role' => 'system', 'content' => 'You are a friendly assistant that analyzes student grades by subject and provides clear, actionable insights. Always return JSON. Do not include PII.'],
            ['role' => 'user', 'content' => json_encode([
                'student_id' => 'STU-1234',
                'grades' => [
                    'Mathematics' => 82,
                    'English'     => 74,
                    'Science'     => 91,
                    'History'     => 68,
                    'Art'         => 95,
                ],
                'term' => '2026 Q1'
            ])],
        ];

        // Cloudflare payload shape depends on the model; this matches the messages pattern.
        $payload = ['messages' => $messages];

        // Call your service
        $result = $ai->run('@cf/meta/llama-3.2-3b-instruct', $payload);

        return response()->json(['success' => true, 'result' => $result]);
    }
}
