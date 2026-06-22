<?php

use App\Http\Resources\PendingEvaluationResource;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;

it('exposes gender and noun on the seminar type for a typed evaluation', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);
    $seminar = Seminar::factory()->create(['seminar_type_id' => $type->id]);
    $registration = Registration::factory()->create(['seminar_id' => $seminar->id]);

    $array = (new PendingEvaluationResource($registration))->toArray(request());

    expect($array['seminar']['seminar_type'])->toMatchArray([
        'name' => 'Seminário',
        'gender' => 'm',
        'noun' => 'seminário',
    ]);
});

it('returns a null seminar type for an untyped evaluation', function () {
    $seminar = Seminar::factory()->create(['seminar_type_id' => null]);
    $registration = Registration::factory()->create(['seminar_id' => $seminar->id]);

    $array = (new PendingEvaluationResource($registration))->toArray(request());

    expect($array['seminar']['seminar_type'])->toBeNull();
});
