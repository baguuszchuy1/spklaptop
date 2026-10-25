<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criteria';

    protected $fillable = [
        'code',
        'name',
        'type',
        'spec_field',
        'scoring_mode',
        'sort_order',
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(CriterionLevel::class)->orderBy('score');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function isCost(): bool
    {
        return $this->type === 'cost';
    }

    public function usesRubric(): bool
    {
        return $this->scoring_mode === 'rubric';
    }
}
