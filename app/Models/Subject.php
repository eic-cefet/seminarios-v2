<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = ['name'];

    public function seminars(): BelongsToMany
    {
        return $this->belongsToMany(Seminar::class, 'seminar_subject');
    }
}
