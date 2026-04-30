<?php

namespace App\Http\Requests\External;

use App\Models\Seminar;
use App\Support\External\SortParser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ExternalSeminarIndexRequest extends FormRequest
{
    /** @var list<string> */
    public const SORTABLE = ['scheduled_at', 'name', 'updated_at'];

    public function authorize(): bool
    {
        return Gate::allows('viewAny', Seminar::class);
    }

    /**
     * Normalize string boolean inputs ("true"/"false") so the `boolean`
     * validation rule accepts them in addition to native bools and "1"/"0".
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['active', 'upcoming'] as $key) {
            if (! $this->has($key)) {
                continue;
            }
            $normalized[$key] = filter_var(
                $this->input($key),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            ) ?? $this->input($key);
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'scheduled_from' => ['sometimes', 'date'],
            'scheduled_to' => ['sometimes', 'date', 'after_or_equal:scheduled_from'],
            'upcoming' => ['sometimes', 'boolean'],
            'updated_since' => ['sometimes', 'date'],
            'sort' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            try {
                SortParser::parse($this->query('sort'), self::SORTABLE);
            } catch (InvalidArgumentException $e) {
                $v->errors()->add('sort', $e->getMessage());
            }
        });
    }

    /**
     * @return list<array{0: string, 1: 'asc'|'desc'}>
     */
    public function sortPairs(): array
    {
        return SortParser::parse($this->validated()['sort'] ?? null, self::SORTABLE);
    }
}
