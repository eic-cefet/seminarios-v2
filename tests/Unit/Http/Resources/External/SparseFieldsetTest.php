<?php

use App\Http\Resources\External\Concerns\SparseFieldset;
use Illuminate\Http\Request;

class SparseFieldsetFixture
{
    use SparseFieldset;

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

it('returns the original payload when ?fields is missing', function () {
    $payload = ['a' => 1, 'b' => 2];
    expect((new SparseFieldsetFixture)->call($payload, new Request, ['a', 'b']))->toBe($payload);
});

it('returns the original payload when ?fields is blank', function () {
    $req = Request::create('/x?fields=');
    $payload = ['a' => 1, 'b' => 2];
    expect((new SparseFieldsetFixture)->call($payload, $req, ['a', 'b']))->toBe($payload);
});

it('returns the original payload when ?fields is whitespace only', function () {
    $req = Request::create('/x?fields=%20%20');
    $payload = ['a' => 1, 'b' => 2];
    expect((new SparseFieldsetFixture)->call($payload, $req, ['a', 'b']))->toBe($payload);
});

it('returns the original payload when ?fields is not a string', function () {
    $req = Request::create('/x?fields[]=a');
    $payload = ['a' => 1, 'b' => 2];
    expect((new SparseFieldsetFixture)->call($payload, $req, ['a', 'b']))->toBe($payload);
});

it('keeps only requested fields', function () {
    $req = Request::create('/x?fields=a');
    expect((new SparseFieldsetFixture)->call(['a' => 1, 'b' => 2], $req, ['a', 'b']))->toBe(['a' => 1]);
});

it('trims whitespace and skips empty entries between commas', function () {
    $req = Request::create('/x?fields=a,%20,b');
    expect((new SparseFieldsetFixture)->call(['a' => 1, 'b' => 2, 'c' => 3], $req, ['a', 'b', 'c']))
        ->toBe(['a' => 1, 'b' => 2]);
});

it('throws 422 on unknown field', function () {
    $req = Request::create('/x?fields=z');
    (new SparseFieldsetFixture)->call(['a' => 1], $req, ['a']);
})->throws(\Illuminate\Validation\ValidationException::class);
