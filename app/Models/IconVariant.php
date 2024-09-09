<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IconVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'file_path',
    ];

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Icon::class);
    }
}
