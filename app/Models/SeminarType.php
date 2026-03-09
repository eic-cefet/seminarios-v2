<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeminarType extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name'];

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class);
    }
}
