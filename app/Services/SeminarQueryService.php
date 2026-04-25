<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class SeminarQueryService
{
    public const LIST_RELATIONS = [
        'seminarType',
        'subjects',
        'speakers.speakerData',
        'seminarLocation',
    ];

    public const DETAIL_RELATIONS = [
        'seminarType',
        'subjects',
        'speakers.speakerData',
        'seminarLocation',
        'workshop',
    ];

    public function forList(Builder $query): Builder
    {
        return $query->with(self::LIST_RELATIONS)->withCount('registrations');
    }

    public function forDetail(Builder $query): Builder
    {
        return $query->with(self::DETAIL_RELATIONS)->withCount('registrations');
    }
}
