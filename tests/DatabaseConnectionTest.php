<?php
namespace Database;

require_once('/usr/share/php/phpmock/phpunit/autoload.php');

use \PHPUnit\Framework\TestCase;
use \Database\DatabaseConnection;
use \Utils\DatabaseError;

final class DatabaseConnectionTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    public DatabaseConnection $db;
    public static string $table_name = 'table_name';
    public static $empty_array = [];


    public function setUp(): void
    {
        $this->getMockBuilder(DatabaseConnection::class)
            ->onlyMethods(['__construct', '__destruct'])
            ->getMock();
        $this->db = new DatabaseConnection();
    }

    public function getPgConvertMock(array $expected_values)
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_convert')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, $expected_values)
            ->willReturnCallback(
                function($dbconn, $table_name, $expected_values)
                {
                    $converted = [];
                    foreach ($expected_values as $key => $value)
                    {
                        $converted["\"$key\""] = $value;
                    }
                    return $converted;
                }
        );
    }

    // tests for insert

    public function test_insert()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_insert')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, self::$empty_array)
            ->willReturn(true);

        $this->db->insert(self::$table_name, self::$empty_array);
    }

    public function test_snsert_WithDBError()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_insert')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, self::$empty_array)
            ->willReturn(false);

        $this->expectException(DatabaseError::class);
        $this->expectExceptionMessage("Failed to insert into '" . self::$table_name . "' table");
        $this->db->insert(self::$table_name, self::$empty_array);
    }

    
    // tests for delete

    public function test_delete()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_delete')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, self::$empty_array)
            ->willReturn(true);

        $this->db->delete(self::$table_name, self::$empty_array);
    }

    public function test_selete_WithDBError()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_delete')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, self::$empty_array)
            ->willReturn(false);

        $this->expectException(DatabaseError::class);
        $this->expectExceptionMessage("Failed to delete from '" . self::$table_name . "' table");
        $this->db->delete(self::$table_name, self::$empty_array);
    }


    // tests for select

    public function simpleConditionsProvider()
    {
        return [
            'with non-empty result' => [['some_content']],
            'with empty result' => [[]],
        ];
    }

    /**
     * @dataProvider simpleConditionsProvider
     */
    public function test_select($pg_select_result)
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_select')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, self::$empty_array)
            ->willReturn($pg_select_result);

        $res = $this->db->select(self::$table_name, self::$empty_array);
        $this->assertSame($res, $pg_select_result, 'select method return wrong value');
    }


    // tests for selectWhere

    public function test_select_WithDBError()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_select')
            ->expects($this->once())
            ->with($this->anything(), self::$table_name, self::$empty_array)
            ->willReturn(false);

        $this->expectException(DatabaseError::class);
        $this->expectExceptionMessage("Failed to select from '" . self::$table_name . "' table");
        $this->db->select(self::$table_name, self::$empty_array);
    }


    public function conditionsProvider()
    {
        return [
            'with valid lookups' => [
                'conditions' => [
                    'a__gt' => 1, 
                    'b__lt' => 2, 
                    'c__gte' => 3, 
                    'd__lte' => 4, 
                    'e__not' => 5, 
                    'f' => 6
                ],
                'expected_values' => [
                    'a' => 1, 
                    'b' => 2, 
                    'c' => 3, 
                    'd' => 4, 
                    'e' => 5,
                    'f' => 6
                ],
                'expected_query' => 
                    'SELECT * FROM '
                    . self::$table_name
                    . ' WHERE '
                    . '"a" > 1 AND '
                    . '"b" < 2 AND '
                    . '"c" >= 3 AND '
                    . '"d" <= 4 AND '
                    . '"e" != 5 AND '
                    . '"f" = 6'
            ],
            'with empty condition array' => [
                'conditions' => [],
                'expected_values' => [],
                'expected_query' => 'SELECT * FROM ' . self::$table_name
            ]
        ];
    }

    /**
    * @dataProvider conditionsProvider
    */
    public function test_selectWhere($conditions, $expected_values, $expected_query)
    {
        $this->getPgConvertMock($expected_values);

        $this->getFunctionMock(__NAMESPACE__, 'pg_query')
            ->expects($this->once())
            ->with($this->anything(), $expected_query)
            ->willReturn('query_result');

        $this->getFunctionMock(__NAMESPACE__, 'pg_fetch_all')
            ->expects($this->once())
            ->with('query_result')
            ->willReturn(['array']);

        $res = $this->db->selectWhere(self::$table_name, $conditions);
        $this->assertSame($res, ['array'], 'selectWhere method returns wrong result');
    }

    public function test_selectWhere_withPgConvertFailed()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_convert')
            ->expects($this->once())
            ->willReturn(false);

        $this->getFunctionMock(__NAMESPACE__, 'pg_query')
            ->expects($this->never());

        $this->expectException(DatabaseError::class);
        $this->expectExceptionMessage('Failed to convert data for \'' . self::$table_name . '\' table');
        $this->db->selectWhere(self::$table_name, ['a' => 1]);
    }

    public function test_selectWhere_withPgQueryFailed()
    {
        $this->getPgConvertMock(['a' => 1]);

        $this->getFunctionMock(__NAMESPACE__, 'pg_query')
            ->expects($this->once())
            ->willReturn(false);

        $this->getFunctionMock(__NAMESPACE__, 'pg_fetch_all')
            ->expects($this->never());

        $this->expectException(DatabaseError::class);
        $this->expectExceptionMessage('Failed to select from \'' . self::$table_name . '\' table');
        $this->db->selectWhere(self::$table_name, ['a' => 1]);
    }


    // tests for deleteByIds

    public function deleteByIds_validValuesProvider()
    {
        return [
            'with integer id array' => [1, 2, 3],
            'with setring id array' => ['1', '2', '3'],
        ];
    }

    /**
     * @dataProvider deleteByIds_validValuesProvider
     */
    public function test_deleteByIds()
    {
        $ids = [1,2,3];
        $id_field_name = 'id';
        $expected_query = 'DELETE FROM ' . self::$table_name . " WHERE id IN (1,2,3)";

        $this->getFunctionMock(__NAMESPACE__, 'pg_query')
            ->expects($this->once())
            ->with($this->anything(), $expected_query)
            ->willReturn(true);

        $this->db->deleteByIds(self::$table_name, $id_field_name, $ids);
    }

    public function test_deleteByIds_withEmptyIdsArray()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_query')
            ->expects($this->never());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('An array with id values must not be empty');
        $this->db->deleteByIds(self::$table_name, 'id', []);
    }

    public function test_deleteByIds_withDBError()
    {
        $this->getFunctionMock(__NAMESPACE__, 'pg_query')
            ->expects($this->once())
            ->willReturn(false);

        $this->expectException(DatabaseError::class);
        $this->expectExceptionMessage('Failed to delete from \'' . self::$table_name . '\' table');
        $this->db->deleteByIds(self::$table_name, 'id', [1, 2, 3]);
    }


    // tests for split

    public function valuesForSplitProvider()
    {
        return [
            'witn one separator' => ['a__b', ['a', 'b']],
            'with two separator' => ['a__b__c', ['a', 'b__c']],
            'without separator' => ['a', ['a', null]],
        ];
    }

    /**
     * @dataProvider valuesForSplitProvider
     */
    public function test_split($value, $expected_result)
    {
        $result = DatabaseConnection::split($value);
        $this->assertSame($expected_result, $result);
    }

}