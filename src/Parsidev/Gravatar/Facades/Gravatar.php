<?php

namespace Parsidev\Gravatar\Facades;

use Illuminate\Support\Facades\Facade;

class Gravatar extends Facade {

    protected static function getFacadeAccessor() {
        return 'gravatar';
    }
}
