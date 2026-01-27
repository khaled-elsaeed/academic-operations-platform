<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'faculty_id',
    ];

    /**
     * Get the faculty of the program.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the students for the program.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function curriculumElectiveGroups()
    {
        return $this->hasMany(CurriculumElectiveGroup::class);
    }

    public function studyPlan()
    {
        return $this->hasMany(StudyPlan::class);
    }


}
