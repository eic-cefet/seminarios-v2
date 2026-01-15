<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    protected $fillable = ['name', 'description'];

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class);
    }
}
