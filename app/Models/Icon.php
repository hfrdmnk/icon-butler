<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'keywords', 'embedding'];

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'icon_id');
    }
}
