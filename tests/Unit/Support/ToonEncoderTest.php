<?php

use App\Support\ToonEncoder;

it('encodes flat scalar key-value pairs', function () {
    $toon = ToonEncoder::encode(['name' => 'João Silva', 'age' => 21, 'active' => true]);

    expect($toon)->toBe("name: João Silva\nage: 21\nactive: true");
});

it('encodes null as an empty value', function () {
    expect(ToonEncoder::encode(['note' => null]))->toBe('note: ');
});

it('encodes nested associative arrays with indentation', function () {
    $toon = ToonEncoder::encode([
        'student' => ['name' => 'João Silva', 'course' => 'Engenharia'],
        'semester' => '2026.1',
    ]);

    expect($toon)->toBe("student:\n  name: João Silva\n  course: Engenharia\nsemester: 2026.1");
});

it('encodes a list of uniform objects as a tabular block', function () {
    $toon = ToonEncoder::encode([
        'by_type' => [
            ['type' => 'Seminário', 'attended' => 3, 'hours' => 4.5],
            ['type' => 'Workshop', 'attended' => 2, 'hours' => 3.0],
        ],
    ]);

    expect($toon)->toBe(
        "by_type[2]{type,attended,hours}:\n  Seminário,3,4.5\n  Workshop,2,3"
    );
});

it('encodes an empty list', function () {
    expect(ToonEncoder::encode(['by_type' => []]))->toBe('by_type[0]:');
});

it('encodes a list of scalars inline', function () {
    expect(ToonEncoder::encode(['tags' => ['a', 'b', 'c']]))->toBe('tags[3]: a,b,c');
});

it('quotes values containing commas or quotes', function () {
    $toon = ToonEncoder::encode(['comment' => 'Ótimo, muito bom "demais"']);

    expect($toon)->toBe('comment: "Ótimo, muito bom ""demais"""');
});

it('throws when tabular rows have mismatched fields', function () {
    ToonEncoder::encode([
        'by_type' => [
            ['type' => 'Seminário', 'attended' => 3],
            ['type' => 'Workshop', 'hours' => 3],
        ],
    ]);
})->throws(InvalidArgumentException::class);
