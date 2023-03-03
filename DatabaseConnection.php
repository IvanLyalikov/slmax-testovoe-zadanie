<?php

require_once('autoload.php');
require_once('exceptions.php');


/**
 * Class for connecting to the PostgreSQL database and making queries.
 */
class DatabaseConnection
{
    protected $dbconn;

    /**
     * Create new class object and connect to a database.
     * If an argument is null then the value from `Settings`
     * class will be used.
     */
    function __construct(
            string|null $host = null,
            string|null $port = null,
            string|null $dbname = null,
            string|null $user = null,
            string|null $password = null)
    {
        $conn_str = 'host=%s port=%s dbname=%s user=%s password=%s';
        $this->dbconn = pg_connect(sprintf(
            $conn_str,
            $host ?? Settings::DB_HOST,
            $port ?? Settings::DB_POST,
            $dbname ?? Settings::DB_DBNAME,
            $user ?? Settings::DB_USER,
            $password ?? Settings::DB_PASSWORD,
        ));
    }

    function __destruct()
    {
        pg_close($this->dbconn);
    }

    /**
     * Insert values into `table_name`. Throw `DatabaseError` on failire.
     * @param string $table_name Table for insert.
     * @param array $values An array whose keys are field names in the table
     * table_name, and whose values are the values of those fields that are
     * to be inserted.
     */
    public function insert(string $table_name, array $values)
    {
        $res = pg_insert($this->dbconn, $table_name, $values);
        if (! $res)
        {
            throw new DatabaseError("Failed to insert into '$table_name' table");
        }
    }

    /**
     * Delete values from `table_name`. Throw `DatabaseError` on failire.
     * @param string $table_name Table from which to delete.
     * @param array $values An array whose keys are field names in the 
     * table table_name, and whose values are the values of those fields 
     * that are to be deleted.
     */
    public function delete(string $table_name, array $conditions)
    {
        $res = pg_delete($this->dbconn, $table_name, $conditions);
        if (! $res)
        {
            throw new DatabaseError("Failed to delete from '$table_name' table");
        }
    }

    /**
     * Selects records specified by conditions which has field=>value.
     * Throw `DatabaseError` on failire.
     * @param string $table_name Table from which to select.
     * @param array $conditions An array whose keys are field names in the 
     * table table_name, and whose values are the values of those fields 
     * that are to be selected.
     */
    public function select(string $table_name, array $conditions)
    {
        $res = pg_select($this->dbconn, $table_name, $conditions);
        if (! $res)
        {
            throw new DatabaseError("Failed to select from '$table_name' table");
        }
        return $res;
    }

    /**
     * Selects records specified by conditions.
     * Throw `DatabaseError` on failire.
     * @param string $table_name Table from which to select.
     * @param array $conditions An array whose keys are in the format:
     * `field_name`__`lookup` and whose values are the values of those
     * fields that are to be selected.
     *
     * Lookups then are replaced to operators in the WHERE clause of the SELECT statment.
     * Possible `lookup` values:
     *  - `gt` replaced to `>`
     *  - `lt` replaced to `<`
     *  - `gte` replaced to `>=`
     *  - `lte` replaced to `<=`
     *  - `not` replaced to `!=`
     * If only field name is provided then `=` operator will be used in SQL query.
     */
    public function selectWhere(string $table_name, array $conditions)
    {
        $lookups = $this->getLookups($conditions);
        $fields = $this->removeLookups($conditions);
        $fields = pg_convert($this->dbconn, $table_name, $fields);
        if (! $fields)
        {
            throw new DatabaseError("Failed to convert data for use with '$table_name' table");
        }

        //$this->escapeFieldNames($fields);
        $result = pg_query(
            $this->dbconn, 
            "SELECT * FROM $table_name {$this->buildWhereExpression($fields, $lookups)}",
        );
        if (! $result)
        {
            throw new DatabaseError("Failed to select from '$table_name' table");
        }
        return pg_fetch_all($result);
    }

    /**
     * Delete values from `table_name`. Throw `DatabaseError` on failire.
     * @param string $table_name Table from which to delete.
     * @param string $id_field_name Name of primary key column in the `table_name`.
     * @param array $ids Primary key values which specify the rows to delete.
     */
    public function deleteByIds(string $table_name, string $id_field_name, array $ids)
    {
        $this->escapeLitarals($ids);
        $ids = implode(',', $ids);
        $res = pg_query($this->dbconn, "DELETE FROM $table_name WHERE $id_field_name IN ($ids)");
        if (! $res)
        {
            throw new DatabaseError("Failed to delete from '$table_name' table");
        }
    }

    /**
     * Build expression for the WHERE clause.
     * All conditions are concatanated by AND operator.
     * @param array $fields An array where keys are field names and values are
     * values for compare in the WHERE clause.
     * @param array $lookups An array where keys are field names and values are
     * lookup names that will be replaced to the comparision operatopr in the WHERE clause.
     * @return string Espression including WHERE word in the beginning and conditions joined
     * by AND operator.
     */
    protected function buildWhereExpression(array $fileds, array $lookups): string
    {
        $operators = [
            'gt' => '>',
            'lt' => '<',
            'gte' => '>=',
            'lte' => '<=',
            'not' => '!=',
        ];
        
        $where_expression = '';
        foreach ($fileds as $field_name => $value)
        {
            $lookup = $lookups[$field_name];
            if (!is_null($lookup))
            {
                if (!array_key_exists($lookup, $operators))
                {
                    throw new Exception('Invalid lookup "{$lookup}"');
                }
                $operator = $operators[$lookup];
            }
            else
            {
                $operator = '=';
            }

            $where_expression .= "$field_name $operator $value";
            if (array_key_last($fileds) !== $field_name)
            {
                $where_expression .= ' and ';
            }
        }
        if ($where_expression)
        {
            $where_expression = 'WHERE ' . $where_expression;
        }
        return $where_expression;
    }

    
    protected function escapeLitarals(array &$values)
    {
        foreach ($values as &$value)
        {
            if (is_string($value))
            {
                $value = pg_escape_literal($this->dbconn, $value);
            }
        }
    }


    protected function split(string $value): array
    {
        $splited = explode('__', $value, 2);
        $field_name = $splited[0];
        $lookup = (count($splited) == 2) ? $splited[1] : null;
        return [$field_name, $lookup];
    }

    protected function getLookups($conditions, bool $add_quotes = true): array
    {
        foreach ($conditions as $key => $value)
        {
            [$field_name, $lookup] = $this->split($key);
            if ($add_quotes)
            {
                $field_name = "\"$field_name\"";
            }
            $lookups[$field_name] = $lookup;
        }
        return $lookups;
    }

    protected function removeLookups($conditions): array
    {
        foreach ($conditions as $key => $value)
        {
            [$field_name, $lookup] = $this->split($key);
            if (!is_null($lookup))
            {
                $conditions[$field_name] = $value;
                unset($conditions[$key]);
            }
        }
        return $conditions;
    }
}