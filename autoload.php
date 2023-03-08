<?php

spl_autoload_register('Autoloader::loadClass');
spl_autoload_register('Autoloader::loadException');

class Autoloader
{
    public static function loadClass($className)
    {
        $splited = explode('\\', $className);
        $path = end($splited) . '.php';
        if (file_exists($path))
        {
            include_once $path;
        }
    }

    public static function loadException($className)
    {
        if (preg_match('/Error$/', $className))
        {
            include_once 'exceptions.php';
        }
    }
}
