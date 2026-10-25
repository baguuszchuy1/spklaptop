<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_respondent_id',
        'criterion_id',
        'likert',
        'priority_rank',
    ];

    protected $casts = [
        'likert' => 'integer',
        'priority_rank' => 'integer',
    ];

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(SurveyRespondent::class, 'survey_respondent_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
