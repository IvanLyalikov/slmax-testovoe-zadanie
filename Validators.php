<?php

require_once('autoload.php');

class Validators
{
    protected static function getErrMsg(string $err_prefix, string $msg): string
    {
        return "$err_prefix: " . $msg;
    }

    public static function validateMaxLength(string $str, int $max_length, string $err_prefix = '')
    {
        if (strlen($str) > 30)
        {
            throw new ValidationError(self::getErrMsg($err_prefix,
                "The value $str has length greater that $max_length"));
        }
    }

    public static function validateAlphabeic(string $str, string $err_prefix = '')
    {
        if (!ctype_alpha($str))
        {
            throw new ValidationError(self::getErrMsg($err_prefix, 
                "The value $str contains non-alphabetic charachers"));
        }
    }

    public static function validateNumeric(string $str, string $err_prefix = '')
    {
        if (!ctype_digit($str))
        {
            throw new ValidationError(self::getErrMsg($err_prefix, 
                "The value $str contains non-numeric charachers"));
        }
    }

    public static function validateDatetime(string $str, string $err_prefix = '')
    {
        $str = date_create($str);
        if (! $str)
        {
            throw new ValidationError(self::getErrMsg($err_prefix,  
                "The value $str cannot be interpreted as datetime"));
        }
    }

    public static function validateBit(string|int $value, string $err_prefix = '')
    {
        if (!in_array($value, [0, 1]))
        {
            throw new ValidationError(self::getErrMsg($err_prefix,
                "The value $value cannot be interpreted as bit"));
        }
    }
}