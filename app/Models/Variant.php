<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'file_path', 'icon_id'];

    public function icon()
    {
        return $this->belongsTo(Icon::class);
    }
}
