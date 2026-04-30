<?php

use App\Http\Resources\External\Concerns\SparseFieldset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class SparseFieldsetFixture extends JsonResource
{
    use SparseFieldset;

    /** @return list<string> */
    public function availableFields(): array
    {
        return ['a', 'b', 'c'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $available
     * @return array<string, mixed>
     */
    public function call(array $payload, Request $request, array $available): array
    {
        return $this->applyFieldset($payload, $request, $available);
    }
}

beforeEach(function () {
    $this->fixture = new SparseFieldsetFixture(null);
});

it('returns the original payload when ?fields is missing', function () {
    $payload = ['a' => 1, 'b' => 2];
    expect($this->fixture->call($payload, new Request, ['a', 'b']))->toBe($payload);
});

it('returns the original payload when ?fields is blank', function () {
    $req = Request::create('/x?fields=');
    $payload = ['a' => 1, 'b' => 2];
    expect($this->fixture->call($payload, $req, ['a', 'b']))->toBe($payload);
});

it('returns the original payload when ?fields is whitespace only', function () {
    $req = Request::create('/x?fields=%20%20');
    $payload = ['a' => 1, 'b' => 2];
    expect($this->fixture->call($payload, $req, ['a', 'b']))->toBe($payload);
});

it('returns the original payload when ?fields is not a string', function () {
    $req = Request::create('/x?fields[]=a');
    $payload = ['a' => 1, 'b' => 2];
    expect($this->fixture->call($payload, $req, ['a', 'b']))->toBe($payload);
});

it('keeps only requested fields', function () {
    $req = Request::create('/x?fields=a');
    expect($this->fixture->call(['a' => 1, 'b' => 2], $req, ['a', 'b']))->toBe(['a' => 1]);
});

it('trims whitespace and skips empty entries between commas', function () {
    $req = Request::create('/x?fields=a,%20,b');
    expect($this->fixture->call(['a' => 1, 'b' => 2, 'c' => 3], $req, ['a', 'b', 'c']))
        ->toBe(['a' => 1, 'b' => 2]);
});

it('throws 422 on unknown field', function () {
    $req = Request::create('/x?fields=z');
    $this->fixture->call(['a' => 1], $req, ['a']);
})->throws(ValidationException::class);
