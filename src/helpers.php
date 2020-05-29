<?php

/**
 * It calls on all callbacks to the first, which did not return null.
 * The resulting value is the result of the function and returns.
 *
 * @param mixed ...$callbacks
 *
 * @return mixed
 */
function elseChain(...$callbacks)
{
    $value = null;

    foreach ($callbacks as $callback) {
        $value = $callback();

        if (!empty($value)) {
            return $value;
        }
    }

    return $value;
}
