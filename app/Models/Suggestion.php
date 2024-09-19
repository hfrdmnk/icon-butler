<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Suggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'copy',
        'generated_keywords',
    ];

    public function icons(): BelongsToMany
    {
        return $this->belongsToMany(Icon::class);
    }

    public function iconSet(): BelongsTo
    {
        return $this->belongsTo(IconSet::class);
    }
}
