<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyRespondent extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'cohort_year',
        'laptop_used',
        'source',
        'external_ref',
        'submitted_at',
    ];

    protected $casts = [
        'cohort_year' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
