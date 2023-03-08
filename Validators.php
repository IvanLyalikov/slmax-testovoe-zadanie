<?php
namespace Utils;

require_once('autoload.php');

/**
 * Class that contains validators as static methods.
 */
class Validators
{
    protected static function getErrMsg(string $err_prefix, string $msg): string
    {
        return "$err_prefix: " . $msg;
    }

    /**
     * Throw `ValidationError` if the `str` length greater than `max_length`.
     * `err_prefix` is added before default error message.
     */
    public static function validateMaxLength(string $str, int $max_length, string $err_prefix = '')
    {
        if (strlen($str) > $max_length)
        {
            throw new ValidationError(self::getErrMsg($err_prefix,
                "The value $str has length greater that $max_length"));
        }
    }

     /**
     * Throw `ValidationError` if the `str` has at least one non-alphabetic cahracher.
     * `err_prefix` is added before default error message.
     */
    public static function validateAlphabeic(string $str, string $err_prefix = '')
    {
        if (!ctype_alpha($str))
        {
            throw new ValidationError(self::getErrMsg($err_prefix, 
                "The value $str contains non-alphabetic charachers"));
        }
    }

     /**
     * Throw `ValidationError` if the `str` has at least one non-numerical characher.
     * `err_prefix` is added before default error message.
     */
    public static function validateNumeric(string $str, string $err_prefix = '')
    {
        if (!ctype_digit($str))
        {
            throw new ValidationError(self::getErrMsg($err_prefix, 
                "The value $str contains non-numeric charachers"));
        }
    }

     /**
     * Throw `ValidationError` if `date_create($str)` function returns false.
     * `err_prefix` is added before default error message.
     */
    public static function validateDatetime(string $str, string $err_prefix = '')
    {
        $res = date_create($str);
        if (! $res)
        {
            throw new ValidationError(self::getErrMsg($err_prefix,  
                "The value $str cannot be interpreted as datetime"));
        }
    }

     /**
     * Throw `ValidationError` if the `value` cannot be interpreted as `0` or `1`.
     * `err_prefix` is added before default error message.
     */
    public static function validateBit(string|int|bool $value, string $err_prefix = '')
    {
        if (!in_array($value, [0, 1]))
        {
            throw new ValidationError(self::getErrMsg($err_prefix,
                "The value $value cannot be interpreted as bit"));
        }
    }
}