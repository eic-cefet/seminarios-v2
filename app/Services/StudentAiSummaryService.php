<?php

namespace App\Services;

use App\Models\User;
use App\Support\SemesterRange;
use App\Support\ToonEncoder;
use Illuminate\Support\Facades\Cache;

class StudentAiSummaryService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
    Você é um assistente que resume o engajamento de um aluno em seminários e
    workshops para um professor ou administrador. Escreva um resumo curto (3 a
    5 frases), em português, destacando participação, faltas e pontos de
    atenção. Os dados de entrada estão no formato TOON (Token-Oriented Object
    Notation), um formato tabular compacto similar a YAML. Retorne APENAS o
    texto do resumo, sem explicações adicionais.
    PROMPT;

    public function __construct(
        private readonly StudentDashboardService $dashboard,
        private readonly ?AiService $ai,
    ) {}

    public function summaryFor(User $student, SemesterRange $range, User $viewer): ?string
    {
        if (! $this->ai) {
            return null;
        }

        $cacheKey = "student_ai_summary:{$student->id}:{$range->toString()}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($student, $range, $viewer) {
            $data = $this->dashboard->forStudent($student, $range, $viewer);

            $payload = ToonEncoder::encode([
                'student' => [
                    'name' => $student->name,
                    'course' => $student->studentData?->course?->name ?? 'N/A',
                    'situation' => $student->studentData?->course_situation?->value ?? 'N/A',
                ],
                'semester' => $range->toString(),
                'totals' => $data['totals'],
                'by_type' => $data['by_type'],
            ]);

            return $this->ai->chat(self::SYSTEM_PROMPT, $payload, 512);
        });
    }
}
