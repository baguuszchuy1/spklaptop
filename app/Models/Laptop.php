<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laptop extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'price',
        'processor_name',
        'processor_score',
        'ram_gb',
        'storage_type',
        'storage_gb',
        'spec_source',
        'spec_accessed_on',
        'notes',
    ];

    protected $casts = [
        'price' => 'integer',
        'processor_score' => 'integer',
        'ram_gb' => 'integer',
        'storage_gb' => 'integer',
        'spec_accessed_on' => 'date',
    ];

    public function getPriceLabelAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->price, 0, ',', '.');
    }

    public function getRamLabelAttribute(): string
    {
        return $this->ram_gb . ' GB';
    }

    public function getStorageLabelAttribute(): string
    {
        return $this->storage_type . ' ' . number_format((int) $this->storage_gb, 0, ',', '.') . ' GB';
    }
}
