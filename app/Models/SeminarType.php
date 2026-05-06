<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeminarType extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name', 'gender', 'name_plural'];

    protected $attributes = [
        'gender' => 'masculine',
        'name_plural' => null,
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
        ];
    }

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class);
    }
}
