<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variant extends Model
{
    protected $fillable = ['type', 'file_path'];

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Icon::class);
    }
}
