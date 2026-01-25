<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeminarLocation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'max_vacancies'];

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class);
    }
}
