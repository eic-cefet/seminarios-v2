<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeminarType extends Model
{
    protected $fillable = ['name'];

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class);
    }
}
