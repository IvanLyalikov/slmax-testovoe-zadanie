<?php

spl_autoload_register('Autoloader::loadClass');

class Autoloader
{
    public static function loadClass($className)
    {
        include $className . '.php';
    }

    public static function loadException($className)
    {
        include 'exceptions.php';
    }
}
