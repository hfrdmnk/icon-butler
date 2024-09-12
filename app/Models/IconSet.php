<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IconSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'url',
    ];

    public function icons(): HasMany
    {
        return $this->hasMany(Icon::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }
}
