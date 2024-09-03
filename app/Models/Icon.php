<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Icon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'keywords', 'embedding'];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
