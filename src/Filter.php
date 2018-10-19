<?php

namespace Costamilam\Alpha;

class Filter
{
    private static $filter = array();

    private static $errorMessage = array(
        "minimumValue" => "Insufficient minimum value",
        "maximumValue" => "Maximum value overflow",
        "notNullable" => "Value is not nullable",
        "invalidBoolean" => "Invalid boolean",
        "invalidInt" => "Invalid int",
        "invalidFloat" => "Invalid float"
    );

    public function changeErrorMessage($errorList) {
        foreach ($errorList as $type => $message) {
            self::$errorMessage[$type] = $message;
        }
    }

    public static function create($name, $callback)
    {
        self::$filter[$name] = $callback;

        return true;
    }

    public static function createWithRegExp($name, $regexp, $errorMessage)
    {
        self::$filter[$name] = function ($value, &$status = null) use ($regexp, $errorMessage) {
            if(preg_match($regexp, $value) === 1) {
                $status = array();
            } else {
                $status = array($errorMessage);
            }

            return $value;
        };
    }

    public static function use($name, ...$value)
    {
        return self::$filter[$name](...$value);
    }

    public static function group($validate, &$error)
    {
        $error = array();
        $result = array();

        foreach ($validate as $key => $value) {
            $status = null;
            $function = array_shift($value);

            if (array_key_exists($function, self::$filter)) {
                $value = self::$filter[$function](array_shift($value), $status, ...$value);
            } elseif (is_callable(__CLASS__."::$function")) {
                $value = self::{$function}(array_shift($value), $status, ...$value);
            } else {
                continue;
            }

            if ($status !== array()) {
                $error[$key] = $status;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    public static function isEmpty(...$variable)
    {
        foreach ($variable as $value) {
            if (
                (gettype($variable) === "string" && trim($variable) === "")
                || (is_array($variable) && count($variable) === 0)
                || $variable === null
            ) {
                return true;
            };
        }

        return false;
    }

    private static function filter($value, $isString, &$error, $min = null, $max = null, $nullable = false, $default = null) {
        //Verify min size
        if ($min !== null && ($type && strlen($value) < $min || !$type && $value < $min)) {
            $error[] = self::$errorMessage["minimumValue"];
        }

        //Verify max size
        if ($max !== null && ($type && strlen($value) > $max || !$type && $value > $max)) {
            $error[] = self::$errorMessage["maximumValue"];
        }

        //Verify if is null
        if ($nullable === false && $value === null) {
            $error[] = self::$errorMessage["notNullable"];
        }

        //Return value, default value or null
        if ($error !== array()) {
            if ($default !== null) {
                $error = array();

                return $default;
            } elseif ($nullable === true) {
                $error = array();

                return null;
            }
        }

        return $value;
    }

    public static function filterInt($value, &$error, $sanitize = false, $min = null, $max = null, $nullable = false, $default = null)
    {
        $error = array();

        if ($sanitize === true) {
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT, array("flags" => FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX));
        }

        $validate = filter_var($value, FILTER_VALIDATE_INT, array("flags" => FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX));

        if ($validate === false) {
            $error[] = self::$errorMessage["invalidInt"];

            return $value;
        }

        $value = (int) $validate;

        return self::filter($value, false, $error, $min, $max, $nullable, $default);
    }

    public static function filterFloat($value, &$error, $sanitize = false, $min = null, $max = null, $nullable = false, $default = null)
    {
        $error = array();

        if ($sanitize === true) {
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, array("flags" => FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC));
        }
        $value = str_replace(",", ".", $value);

        $validate = filter_var($value, FILTER_VALIDATE_FLOAT, array("flags" => FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC));

        if ($validate === false) {
            $error[] = self::$errorMessage["invalidFloat"];

            return $value;
        }

        $value = (float) $validate;

        return self::filter($value, false, $error, $min, $max, $nullable, $default);
    }

    public static function filterString($value, &$error, $sanitize = false, $min = null, $max = null, $nullable = false, $default = null)
    {
        $error = array();

        if ($sanitize === true) {
            $value = filter_var($value, FILTER_SANITIZE_STRING);
        }

        $value = (string) $value;
        $value = trim($value);

        return self::filter($value, true, $error, $min, $max, $nullable, $default, $nullable = false, $default = null);
    }

    public static function filterBoolean($value, &$error, $nullable = false, $default = null)
    {
        if ($nullable === false && $value === null) {
            $error[] = self::$errorMessage["notNullable"];
        }

        $validate = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($validate === null && $default !== null) {
            $error = array();

            return $default;
        } elseif (($value === null || $validate === null) && $nullable === true) {
            $error = array();

            return null;
        } else {
            $error = $validate === null ? self::$errorMessage["invalidBoolean"] : array();

            return $value;
        }
    }

    //"Y-m-d H:i:s"
    function filterDateTime($value, &$error, $format = null, $min = null, $max = null, $nullable = false, $default = null) {
        $error = array();

        if ($value instanceof DateTime === false && $value instanceof DateTimeImmutable === false) {
            $value = date_create($value);

            if ($value === false) {
                $error[] = self::$errorMessage["invalidDate"];

                return $value;
            }
        }
        if ($min !== null && $min instanceof DateTime === false && $min instanceof DateTimeImmutable === false) {
            $min = date_create($min);
        }
        if ($max !== null && $max instanceof DateTime === false && $max instanceof DateTimeImmutable === false) {
            $max = date_create($max);
        }

        $value = self::filter($value, false, $error, $min, $max, $nullable, $default);

        if ($value instanceof DateTime || $value instanceof DateTimeImmutable) {
            return $format !== null ? $value->format($format) : $value;
        } else {
            return $value;
        }
    }
}