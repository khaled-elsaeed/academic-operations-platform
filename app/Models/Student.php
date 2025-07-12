<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\AcademicAdvisorScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[ScopedBy([AcademicAdvisorScope::class])]
class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'academic_id',
        'national_id',
        'academic_email',
        'level_id',
        'cgpa',
        'gender',
        'program_id',
    ];

    /**
     * Get the taken hours attribute for the student.
     */
    protected function takenHours(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->enrollments()
                ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->sum('courses.credit_hours')
        );
    }

    /**
     * Get the program of the student.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the level of the student.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the enrollments for the student.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
