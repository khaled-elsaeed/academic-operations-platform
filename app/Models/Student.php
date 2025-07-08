<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'level',
        'cgpa',
        'gender',
        'program_id',
    ];

    /**
     * Get the program of the student.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

}
