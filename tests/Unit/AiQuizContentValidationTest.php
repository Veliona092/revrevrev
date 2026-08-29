<?php

namespace Tests\Unit;

use App\Http\Controllers\ClassManagerController;
use PHPUnit\Framework\TestCase;

class AiQuizContentValidationTest extends TestCase
{
    public function test_duplicate_options_are_rejected(): void
    {
        $this->assertFalse($this->validateQuestion([
            'question' => 'Which protocol provides secure web communication?',
            'options' => [
                'A' => 'HTTPS',
                'B' => 'HTTPS',
                'C' => 'FTP',
                'D' => 'SMTP',
            ],
            'correct' => 'A',
        ]));
    }

    public function test_stem_echo_options_are_rejected(): void
    {
        $this->assertFalse($this->validateQuestion([
            'question' => 'What is the purpose of encryption in network security?',
            'options' => [
                'A' => 'What is the purpose of encryption in network security',
                'B' => 'To protect information from unauthorized access',
                'C' => 'To increase network latency',
                'D' => 'To remove authentication requirements',
            ],
            'correct' => 'B',
        ]));
    }

    public function test_distinct_options_are_accepted(): void
    {
        $this->assertTrue($this->validateQuestion([
            'question' => 'Which protocol provides secure web communication?',
            'options' => [
                'A' => 'HTTPS',
                'B' => 'FTP',
                'C' => 'SMTP',
                'D' => 'DNS',
            ],
            'correct' => 'A',
        ]));
    }

    public function test_legitimate_why_answer_overlap_is_allowed(): void
    {
        $this->assertTrue($this->validateQuestion([
            'question' => 'Why does TCP use a three-way handshake before data transfer?',
            'options' => [
                'A' => 'To establish synchronized sequence numbers before data transfer begins',
                'B' => 'To increase the amount of packet loss during transmission',
                'C' => 'To disable reliable delivery for the connection',
                'D' => 'To remove the need for an acknowledgement response',
            ],
            'correct' => 'A',
        ], 'why'));
    }

    public function test_grounded_evidence_is_required_for_source_based_questions(): void
    {
        $this->assertFalse($this->validateQuestion([
            'question' => 'Which protocol provides secure web communication?',
            'options' => [
                'A' => 'HTTPS',
                'B' => 'FTP',
                'C' => 'SMTP',
                'D' => 'DNS',
            ],
            'correct' => 'A',
            'evidence' => 'This protocol encrypts traffic using an unrelated method.',
        ], 'what', 'The page uses HTTPS to secure communication between the client and the server.'));
    }

    public function test_similar_stems_are_deduplicated_across_batch(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'deduplicateQuestionBatch');
        $result = $method->invoke(new ClassManagerController, [[
            'question' => 'Why does the system use HTTPS for secure communication?',
            'question_type' => 'why',
            'options' => ['A' => 'It encrypts traffic', 'B' => 'It slows the app', 'C' => 'It disables caching', 'D' => 'It removes authentication'],
            'correct' => 'A',
            'evidence' => 'HTTPS secures communication between the client and the server.',
        ], [
            'question' => 'Why does the system use HTTPS to secure communication?',
            'question_type' => 'why',
            'options' => ['A' => 'It encrypts traffic', 'B' => 'It slows the app', 'C' => 'It disables caching', 'D' => 'It removes authentication'],
            'correct' => 'A',
            'evidence' => 'HTTPS secures communication between the client and the server.',
        ]]);

        $this->assertCount(1, $result['questions']);
        $this->assertCount(1, $result['duplicates']);
    }

    public function test_token_based_grounded_evidence_is_accepted(): void
    {
        $this->assertTrue($this->validateQuestion([
            'question' => 'How does the client establish a secure connection?',
            'options' => [
                'A' => 'By using the HTTPS protocol',
                'B' => 'By opening plain TCP',
                'C' => 'By disabling certificates',
                'D' => 'By broadcasting UDP packets',
            ],
            'correct' => 'A',
            'evidence' => 'HTTPS secures communication between client and server',
        ], 'how', 'The web application employs HTTPS to secure all communication between the client and the server across the network.'));
    }

    public function test_cross_batch_semantic_duplicates_with_same_answer_are_caught(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'deduplicateQuestionBatch');
        $result = $method->invoke(new ClassManagerController, [[
            'question' => 'What is the primary role of the independent external auditor?',
            'question_type' => 'what',
            'options' => [
                'A' => 'To provide reasonable assurance on financial statements',
                'B' => 'To prepare internal books',
                'C' => 'To manage corporate payroll',
                'D' => 'To design marketing campaigns',
            ],
            'correct' => 'A',
            'evidence' => 'external auditor provides reasonable assurance on financial statements',
        ], [
            'question' => 'Which of the following best describes the main function of an external auditor?',
            'question_type' => 'what',
            'options' => [
                'A' => 'To give reasonable assurance on the financial statements',
                'B' => 'To calculate tax returns',
                'C' => 'To supervise internal employees',
                'D' => 'To issue company stocks',
            ],
            'correct' => 'A',
            'evidence' => 'external auditor provides reasonable assurance on financial statements',
        ]]);

        $this->assertCount(1, $result['questions']);
        $this->assertCount(1, $result['duplicates']);
    }

    public function test_distinct_questions_are_not_falsely_deduplicated(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'deduplicateQuestionBatch');
        $result = $method->invoke(new ClassManagerController, [[
            'question' => 'What is the definition of assets under the conceptual framework?',
            'question_type' => 'what',
            'options' => ['A' => 'Economic resource controlled', 'B' => 'Present obligation', 'C' => 'Residual interest', 'D' => 'Revenue earned'],
            'correct' => 'A',
        ], [
            'question' => 'What is the definition of liabilities under the conceptual framework?',
            'question_type' => 'what',
            'options' => ['A' => 'Present obligation from past events', 'B' => 'Economic resource controlled', 'C' => 'Residual interest', 'D' => 'Operating expense'],
            'correct' => 'A',
        ]]);

        $this->assertCount(2, $result['questions']);
        $this->assertCount(0, $result['duplicates']);
    }

    public function test_generation_below_acceptance_threshold_does_not_replace_existing_questions(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'shouldReplaceExistingQuestions');

        $this->assertFalse($method->invoke(new ClassManagerController, 40, 9));
        $this->assertTrue($method->invoke(new ClassManagerController, 40, 32));
    }

    public function test_pdf_noise_and_watermarks_are_stripped(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'cleanExtractedPdfText');
        $raw = "International Journal for Multidisciplinary Research (IJFMR) E-ISSN: 2582-2160 Website: www.ijfmr.com Email: editor@ijfmr.com Volume 7, Issue 2, March-April 2025\n"
            ."This study investigates the role of machine learning in automated education assessment.\n"
            ."Page 4 of 12\n"
            ."REFERENCES:\n"
            .'[1] Smith, J. (2024). AI in education.';

        $cleaned = $method->invoke(new ClassManagerController, $raw);

        $this->assertStringNotContainsString('IJFMR', $cleaned);
        $this->assertStringNotContainsString('2582-2160', $cleaned);
        $this->assertStringNotContainsString('editor@ijfmr.com', $cleaned);
        $this->assertStringNotContainsString('Page 4 of 12', $cleaned);
        $this->assertStringNotContainsString('Smith, J.', $cleaned);
        $this->assertStringContainsString('This study investigates the role of machine learning', $cleaned);
    }

    public function test_document_is_sliced_into_sections(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'sliceDocumentIntoSections');
        $text = str_repeat('This is a test sentence explaining machine learning concepts in education. ', 40);

        $sections = $method->invoke(new ClassManagerController, $text, 4);

        $this->assertGreaterThanOrEqual(2, count($sections));
        foreach ($sections as $sec) {
            $this->assertNotEmpty($sec);
        }
    }

    public function test_stem_containment_catches_duplicate_with_trailing_words(): void
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'deduplicateQuestionBatch');
        $result = $method->invoke(new ClassManagerController, [[
            'question' => 'Why do teachers need to provide official references (PDFs, slides, texts) in the PRC Reviewer and Monthly Assessment System?',
            'question_type' => 'why',
            'options' => ['A' => 'To enable study', 'B' => 'To waste time', 'C' => 'To reduce grades', 'D' => 'To delay exams'],
            'correct' => 'A',
        ], [
            'question' => 'Why do teachers need to provide official references?',
            'question_type' => 'why',
            'options' => ['A' => 'To prepare students', 'B' => 'To delete data', 'C' => 'To close classes', 'D' => 'To lock files'],
            'correct' => 'A',
        ]]);

        $this->assertCount(1, $result['questions']);
        $this->assertCount(1, $result['duplicates']);
    }

    private function validateQuestion(array $question, ?string $questionType = null, ?string $sourceText = null): bool
    {
        $method = new \ReflectionMethod(ClassManagerController::class, 'isCleanAiQuestion');

        return $method->invoke(
            new ClassManagerController,
            $question,
            ['A', 'B', 'C', 'D'],
            $questionType,
            $sourceText
        );
    }
}
