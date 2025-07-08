<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Term extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'season',
        'year',
        'is_active',
    ];

    protected $appends = [
        'name'
    ];

    /**
     * Get the name attribute for the term (e.g., "fall 2024").
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->season} {$this->year}"
        );
    }

}
