<?php
namespace Utils;

/**
 * This exception is thrown on validation failure.
 */
class ValidationError extends \Exception
{
    
}

/**
 * This exception is thrown on database failure.
 */
class DatabaseError extends \Exception
{
    
}

/**
 * This exception is thrown if a record with nonexistent id is selected.
 */
class DoesNotExistsError extends \Exception
{
    function __construct(string|int $id, string $table_name, 
            $code = 0, \Throwable|null $previous = null)
    {
        $msg = "Record with id '$id' does not exists in '$table_name' table";
        parent::__construct($msg, $code, $previous);
    }
}