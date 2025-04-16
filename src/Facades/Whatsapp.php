<?php

namespace Renderbit\LaravelWhatsapp\Facades;

use Illuminate\Support\Facades\Facade;

class Whatsapp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp';
    }
}
