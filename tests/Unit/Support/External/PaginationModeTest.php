<?php

use App\Support\External\PaginationMode;

it('defaults to page when no value given', function () {
    expect(PaginationMode::fromQuery(null))->toBe(PaginationMode::Page);
    expect(PaginationMode::fromQuery(''))->toBe(PaginationMode::Page);
});

it('parses cursor', function () {
    expect(PaginationMode::fromQuery('cursor'))->toBe(PaginationMode::Cursor);
});

it('parses page explicitly', function () {
    expect(PaginationMode::fromQuery('page'))->toBe(PaginationMode::Page);
});

it('throws on unknown', function () {
    PaginationMode::fromQuery('mystery');
})->throws(InvalidArgumentException::class);
