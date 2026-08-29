<?php

namespace App\Console\Commands;

use App\Models\QuizQuestion;
use Illuminate\Console\Command;

class AuditAiQuizContent extends Command
{
    protected $signature = 'quiz:audit-ai-content {--module= : Limit the audit to one module ID} {--json : Output machine-readable JSON}';

    protected $description = 'Audit saved quiz questions for duplicate or stem-echo answer choices';

    public function handle(): int
    {
        $choiceLetters = ['A', 'B', 'C', 'D'];
        $query = QuizQuestion::query()->orderBy('id');

        if ($this->option('module') !== null) {
            $query->where('module_id', (int) $this->option('module'));
        }

        $summary = [
            'audited' => 0,
            'clean' => 0,
            'flagged' => 0,
            'duplicate_options' => 0,
            'stem_echo' => 0,
            'empty_question_or_option' => 0,
            'invalid_structure' => 0,
            'items' => [],
        ];

        foreach ($query->cursor() as $question) {
            $summary['audited']++;
            $reason = $this->rejectionReason($question->question_text, $question->options, $question->correct_option, $choiceLetters);

            if ($reason === null) {
                $summary['clean']++;

                continue;
            }

            $summary['flagged']++;
            $summary[$reason]++;
            $summary['items'][] = [
                'id' => $question->id,
                'module_id' => $question->module_id,
                'order' => $question->order,
                'reason' => $reason,
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Audited', $summary['audited']],
                ['Clean', $summary['clean']],
                ['Flagged', $summary['flagged']],
                ['Duplicate options', $summary['duplicate_options']],
                ['Stem echo', $summary['stem_echo']],
                ['Empty question/option', $summary['empty_question_or_option']],
                ['Invalid structure', $summary['invalid_structure']],
            ]
        );

        if ($summary['items'] !== []) {
            $this->table(['Question ID', 'Module ID', 'Order', 'Reason'], array_map(
                static fn (array $item): array => [$item['id'], $item['module_id'], $item['order'], $item['reason']],
                $summary['items']
            ));
        }

        return self::SUCCESS;
    }

    private function rejectionReason(?string $question, mixed $options, ?string $correct, array $choiceLetters): ?string
    {
        if (! is_string($question) || ! is_array($options) || ! is_string($correct) || count($options) !== count($choiceLetters)
            || ! in_array(strtoupper($correct), $choiceLetters, true)) {
            return 'invalid_structure';
        }

        $normalize = static fn (mixed $value): string => trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $value)));
        $normalizedQuestion = $normalize($question);
        $normalizedOptions = array_map($normalize, array_values($options));

        if ($normalizedQuestion === '' || in_array('', $normalizedOptions, true)) {
            return 'empty_question_or_option';
        }

        if (count($normalizedOptions) !== count(array_unique($normalizedOptions))) {
            return 'duplicate_options';
        }

        if (mb_strlen($normalizedQuestion) >= 20) {
            foreach ($normalizedOptions as $option) {
                similar_text($normalizedQuestion, $option, $similarity);
                if ($similarity > 70) {
                    return 'stem_echo';
                }
            }
        }

        return null;
    }
}
