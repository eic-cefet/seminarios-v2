<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'announcement_sent_at'];

    protected function casts(): array
    {
        return [
            'announcement_sent_at' => 'datetime',
        ];
    }

    public function seminars(): HasMany
    {
        return $this->hasMany(Seminar::class);
    }
}
