<?php

namespace Gluck1986\Support\AutoDoc\Traits;

/**
 * @deprecated
*/
trait AutoDocRequestTrait
{

    public static function getRules()
    {
        return (new static)->rules();
    }
}
