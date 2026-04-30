<?php

namespace App\Http\Requests\External;

use App\Models\Workshop;
use App\Support\External\SortParser;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ExternalWorkshopIndexRequest extends FormRequest
{
    /** @var list<string> */
    public const SORTABLE = ['name', 'updated_at'];

    public function authorize(): bool
    {
        return Gate::allows('viewAny', Workshop::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
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
