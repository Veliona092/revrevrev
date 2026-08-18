<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        $now = now();

        $defaults = [
            'feature.quiz_generation_enabled' => true,
            'feature.quiz_insights_enabled' => true,
            'feature.class_summary_enabled' => true,
            'prompt.quiz_generation.system' => 'You are a quiz generator. Output ONLY a JSON array of exactly {num_questions} question objects. No markdown, no backticks, no explanation. Start with [ and end with ].',
            'prompt.quiz_generation.user_template' => "Generate EXACTLY {num_questions} multiple-choice questions. NOT more. NOT less. EXACTLY {num_questions}.\nDifficulty: {difficulty}.\nModule: {module_title}\nDescription: {module_description}\n\nContent:\n{combined_text}\n\nRules:\n- Return ONLY a valid JSON array.\n- The array must have EXACTLY {num_questions} objects.\n- Each object: {\"question\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A|B|C|D\"}\n- No markdown, no backticks, no extra text.\n- Start with [ and end with ]\n- Stop after {num_questions} questions.",
            'prompt.quiz_insights.system' => 'You are a strict tutor. Reply ONLY in the exact format requested. Keep it short and clear. No extra text. Keep in mind that this is not MATH or Any Form of MATH related question.',
            'prompt.quiz_insights.user_template' => "Student scored {score}% on '{module_title}'.\n\n{answers_context}\n\nAnalyze and reply in this exact short format (maximum 3 lines per section):\nStrong Areas: - point1\n - point2\nWeak Areas: - point1\n - point2\nRecommendation: One short sentence.",
            'prompt.class_summary.system' => 'You are an educational performance analyst. Reply in this exact format with line breaks between each section:\n\nClass Average: [value]\nPass/Fail Status: [value]\nWeak Areas:\n- [area 1]\n- [area 2]\nRecommendation: [one sentence]',
            'prompt.class_summary.user_template' => 'Class average: {class_average}%. Pass count: {pass_count}, Fail count: {fail_count}. Weak areas: {weak_summary}.',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('ai_settings')->insert([
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
