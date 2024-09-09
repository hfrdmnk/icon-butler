<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class IconRepository extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'icon.repository';
    }
}
