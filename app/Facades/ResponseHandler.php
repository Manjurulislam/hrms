<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class ResponseHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'api-response';
    }
}
