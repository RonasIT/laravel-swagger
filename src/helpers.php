<?php

function elseChain(...$callbacks) {
    foreach ($callbacks as $callback) {
        $value = $callback();

        if (!empty($value)) {
            return $value;
        }
    }

    return null;
}