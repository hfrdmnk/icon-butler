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
    ];

    public function icons(): HasMany
    {
        return $this->hasMany(Icon::class);
    }
}
