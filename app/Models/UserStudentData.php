<?php

namespace App\Models;

use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStudentData extends Model
{
    use HasFactory;

    protected $table = 'user_student_data';

    protected $fillable = [
        'user_id',
        'course_id',
        'course_situation',
        'course_role',
    ];

    protected function casts(): array
    {
        return [
            'course_situation' => CourseSituation::class,
            'course_role' => CourseRole::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
