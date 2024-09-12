<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_logo',
        'company_name',
        'keywords',
        'embedding',
    ];

    public function iconSet(): BelongsTo
    {
        return $this->belongsTo(IconSet::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(IconVariant::class);
    }

    public function suggestions(): BelongsToMany
    {
        return $this->belongsToMany(Suggestion::class);
    }
}
