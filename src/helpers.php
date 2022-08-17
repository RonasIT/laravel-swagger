<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

/**
 * @deprecated use elseif construction instead
 *
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

/**
 * Round all values in list of floats.
 *
 * @param array $array
 * @return array
 */
function array_round($array)
{
    $keys = array_keys($array);

    $values = array_map(function ($value) {
        if (is_numeric($value)) {
            return round($value);
        }

        return $value;
    }, $array);

    return array_combine($keys, $values);
}

/**
 * Get value of key from every item in list and return list of them
 *
 * @param array|string $array
 * @param string $key
 *
 * @return array
 *
 * @deprecated
 */
function array_lists($array, $key)
{
    return array_map(function ($item) use ($key) {
        return Arr::get($item, $key);
    }, $array);
}

/**
 * Get list of element which placed in $path in $array
 *
 * @param array|string $array
 * @param string $path
 *
 * @return mixed
 */
function array_get_list($array, $path)
{
    if (is_string($path)) {
        $path = explode('.', $path);
    }

    $key = array_shift($path);

    if (empty($path)) {
        return ($key === '*') ? $array : Arr::get($array, $key);
    }

    if ($key === '*') {
        if (empty($array)) {
            return [];
        }

        $values = array_map(function ($item) use ($path) {
            $value = array_get_list($item, $path);

            if (!is_array($value) || is_associative($value)) {
                return [$value];
            }

            return $value;
        }, $array);

        return Arr::collapse($values);
    } else {
        $value = Arr::get($array, $key);

        return array_get_list($value, $path);
    }
}

/**
 * Verifies whether input is associative array or a list
 *
 * @param array $array
 *
 * @return boolean
 */
function is_associative($array)
{
    return $array !== array_values($array);
}

/**
 * Verifies whether input is array or arrays or not
 *
 * @param array $array
 *
 * @return boolean
 */
function is_multidimensional(array $array): bool
{
    return is_array(Arr::first($array));
}

/**
 * Create directory recursively. The native mkdir() function recursively create directory incorrectly.
 * This is solution.
 *
 * @param string $path
 */
function mkdir_recursively($path)
{
    $explodedPath = explode('/', $path);

    $currentPath = $explodedPath[0];

    array_walk($explodedPath, function ($dir) use (&$currentPath) {
        if ($currentPath != '/') {
            $currentPath .= '/' . $dir;
        } else {
            $currentPath .= $dir;
        }

        if (!file_exists($currentPath)) {
            mkdir($currentPath);
        }
    });
}

/**
 * Check equivalency of two arrays
 *
 * @param array $array1
 * @param array $array2
 *
 * @return boolean
 */
function array_equals($array1, $array2)
{
    $collection1 = (new Collection($array1))->sort();
    $collection2 = (new Collection($array2))->sort();

    return $collection1->values() == $collection2->values();
}

/**
 * Return subtraction of two arrays
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array
 */
function array_subtraction($array1, $array2)
{
    $intersection = array_intersect($array1, $array2);

    return array_diff($array1, $intersection);
}

/**
 * Generate GUID
 *
 * @return string
 */
function getGUID()
{
    mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
    $charId = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);// "-"
    return chr(123)// "{"
        . substr($charId, 0, 8) . $hyphen
        . substr($charId, 8, 4) . $hyphen
        . substr($charId, 12, 4) . $hyphen
        . substr($charId, 16, 4) . $hyphen
        . substr($charId, 20, 12)
        . chr(125);// "}"
}

function array_concat($array, $callback)
{
    $content = '';

    foreach ($array as $key => $value) {
        $content .= $callback($value, $key);
    }

    return $content;
}

function rmdir_recursively($dir)
{
    if ($objs = glob($dir . "/*")) {
        foreach ($objs as $obj) {
            is_dir($obj) ? rmdir_recursively($obj) : unlink($obj);
        }
    }
    rmdir($dir);
}

function fPutQuotedCsv($handle, $row, $fd = ',', $quot = '"')
{
    $cells = array_map(function ($cell) use ($quot) {
        if (preg_match("/[;.\",\n]/", $cell)) {
            $cell = $quot . str_replace($quot, "{$quot}{$quot}", $cell) . $quot;
        }

        return $cell;
    }, $row);

    $str = implode($fd, $cells);

    fputs($handle, $str . "\n");

    return strlen($str);
}

function clear_folder($path)
{
    $files = glob("$path/*");

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }

        if (is_dir($file)) {
            clear_folder($file);
        }
    }
}

/**
 * Builds an associative array by gotten keys and values
 *
 * @param array $array
 * @param callable $callback
 *
 * @return array
 */
function array_associate($array, $callback)
{
    $result = [];

    foreach ($array as $key => $value) {
        $callbackResult = $callback($value, $key);

        if (!empty($callbackResult)) {
            $result[$callbackResult['key']] = $callbackResult['value'];
        }
    }

    return $result;
}

function array_duplicate($array)
{
    return array_diff_key($array, array_unique($array));
}

/**
 * Get only unique objects from array by key (array of keys) or by closure
 *
 * @param array $objectsList
 * @param string|callable|array $filter
 *
 * @return array
 */
function array_unique_objects($objectsList, $filter = 'id')
{
    $uniqueKeys = [];

    $uniqueObjects = array_map(function ($object) use (&$uniqueKeys, $filter) {
        if (is_string($filter)) {
            $value = $object[$filter];
        }

        if (is_callable($filter)) {
            $value = $filter($object);
        }

        if (is_array($filter)) {
            $value = Arr::only($object, $filter);
        }

        if (in_array($value, $uniqueKeys)) {
            return null;
        }
        $uniqueKeys[] = $value;

        return $object;
    }, $objectsList);

    return array_filter($uniqueObjects, function ($item) {
        return !is_null($item);
    });
}

/**
 * @deprecated
 *
 * @param array $objectsList
 * @param string|callable|array $filter
 *
 * @return array
 */
function array_unique_object($objectsList, $filter = 'id')
{
    return array_unique_objects($objectsList, $filter);
}

function array_trim($array)
{
    return array_map(
        function ($item) {
            return (is_string($item)) ? trim($item) : $item;
        },
        $array
    );
}

function array_remove_by_field($array, $fieldName, $fieldValue)
{
    $array = array_values($array);
    $key = array_search($fieldValue, array_column($array, $fieldName));
    if ($key !== false) {
        unset($array[$key]);
    }

    return array_values($array);
}

function array_remove_elements($array, $elements)
{
    return array_diff($array, $elements);
}

function prepend_symbols($string, $expectedLength, $symbol)
{
    while (strlen($string) < $expectedLength) {
        $string = "{$symbol}{$string}";
    }

    return $string;
}

function array_default(&$array, $key, $default)
{
    $array[$key] = Arr::get($array, $key, $default);
}

/**
 * inverse transformation from array_dot
 * @param $array
 * @return array
 */
function array_undot($array)
{
    $result = [];

    foreach ($array as $key => $value) {
        Arr::set($result, $key, $value);
    }

    return $result;
}

function extract_last_part(string $string, string $separator = '.'): array
{
    $entities = explode($separator, $string);

    $fieldName = array_pop($entities);

    $relation = implode($separator, $entities);

    return [$fieldName, $relation];
}
