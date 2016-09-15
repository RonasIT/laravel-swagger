<?php

function elseChain(...$values) {
    foreach ($values as $value) {
        if (!empty($value)) {
            return $value;
        }
    }

    return null;
}