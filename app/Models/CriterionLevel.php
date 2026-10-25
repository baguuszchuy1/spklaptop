<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterionLevel extends Model
{
    protected $fillable = [
        'criterion_id',
        'label',
        'min_value',
        'max_value',
        'score',
    ];

    protected $casts = [
        'min_value' => 'float',
        'max_value' => 'float',
        'score' => 'integer',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
