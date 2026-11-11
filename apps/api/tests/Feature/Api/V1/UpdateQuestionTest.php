<?php

namespace Tests\Feature\Api\V1;

use App\Models\Question;
use App\Models\QuestionSeries;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateQuestionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('removeImageValues')]
    public function test_owner_can_edit_a_question_when_form_data_sends_remove_image_value(mixed $removeImage, ?string $expectedImagePath): void
    {
        $owner = User::factory()->create([
            'role' => 'Owner',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $series = QuestionSeries::query()->create([
            'code' => 'Q-0001',
            'created_by' => $owner->id,
        ]);
        Question::query()->create([
            'question_series_id' => $series->id,
            'revision' => 1,
            'title' => 'Soal lama',
            'format' => 'Pilihan ganda',
            'education_level' => 'SMP/MTs',
            'subject' => 'Matematika',
            'framework_version' => '2025.1',
            'topic' => 'Bilangan',
            'subelement' => 'Bilangan',
            'cognitive_level' => 'Pengetahuan',
            'prompt' => 'Pertanyaan lama',
            'options' => ['3', '4'],
            'answer_data' => ['answerKey' => 0],
            'explanation' => 'Pembahasan lama',
            'image_path' => 'questions/original.png',
            'tags' => [],
            'status' => 'Draf',
            'author_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->put('/api/v1/questions/Q-0001/revisions/1', [
                'title' => 'Soal diperbarui',
                'format' => 'Pilihan ganda',
                'topic' => 'Bilangan',
                'level' => 'Pengetahuan',
                'prompt' => 'Pertanyaan diperbarui',
                'options' => ['3', '4'],
                'answerKey' => '0',
                'explanation' => 'Pembahasan diperbarui',
                'commonMistakes' => '',
                'tags' => [],
                'removeImage' => $removeImage,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Soal diperbarui');

        $this->assertDatabaseHas('questions', [
            'question_series_id' => $series->id,
            'revision' => 1,
            'title' => 'Soal diperbarui',
            'image_path' => $expectedImagePath,
        ]);
    }

    public static function removeImageValues(): array
    {
        return [
            'form false string preserves image' => ['false', 'questions/original.png'],
            'form true string removes image' => ['true', null],
            'boolean false preserves image' => [false, 'questions/original.png'],
            'zero string preserves image' => ['0', 'questions/original.png'],
        ];
    }
}
