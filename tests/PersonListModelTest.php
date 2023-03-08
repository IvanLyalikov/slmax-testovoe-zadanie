<?php
namespace Models;

use PHPUnit\Framework\TestCase;
use Database\DatabaseConnection;


final class PersonListModelTest extends TestCase
{
    public $fields_1;
    public $fields_2;
    public $db_mock;
    
    public function setUp(): void
    {
        $this->db_mock = $this->createMock(DatabaseConnection::class);
        $this->fields_1 = [
            'person_id' => '1',
            'first_name' => 'first', 
            'last_name' => 'last', 
            'birthdate' => '2023-01-01', 
            'gender' => '1', 
            'town' => 'town', 
        ];
        $this->fields_2 = [
            'person_id' => '2',
            'first_name' => 'anotherfirst', 
            'last_name' => 'anotehrlast', 
            'birthdate' => '2023-02-02', 
            'gender' => '0', 
            'town' => 'another_town', 
        ];
    }
    
    /**
     * Create PersonListModel object with objects containing
     * fields from `field_array` argument.
     */
    public function createObject(array ...$field_array)
    {
        $this->db_mock
            ->method('selectWhere')
            ->willReturn($field_array);
        return new PersonListModel($this->db_mock);
    }

    // tests for construct

    public function getSelectWhereMock($fields)
    {
        $fields2 = $fields;
        $fields2['person_id'] = '2';
        $this->createMock(DatabaseConnection::class)
            ->expects($this->once())
            ->method('selectWhere')
            ->willReturn([$fields, $fields2]);
    }

    public function test_construct()
    {
        $some_conditions = [
            'person_id__gt' => '1',
            'first_name__lt' => 'name', 
            'last_name__not' => 'name', 
            'birthdate__gte' => '2023-01-01', 
            'gender' => '1', 
            'town' => 'town_name'
        ];
        $this->db_mock
            ->expects($this->once())
            ->method('selectWhere')
            ->with(PersonModel::TABLE_NAME, $some_conditions)
            ->willReturn([$this->fields_1, $this->fields_2]);
        
        $expected_obj1 = new PersonModel($this->db_mock, ...$this->fields_1);
        $expected_obj2 = new PersonModel($this->db_mock, ...$this->fields_2);
        
        $list_obj = new PersonListModel($this->db_mock, ...$some_conditions);
        
        $this->assertEquals([$expected_obj1, $expected_obj2], $list_obj->getObjects());
    }

    public function test_construct_withEmptySelectResult()
    {
        $this->db_mock
            ->expects($this->once())
            ->method('selectWhere')
            ->willReturn([]);
        
        $list_obj = new PersonListModel($this->db_mock);
        $this->assertEquals([], $list_obj->getObjects());
    }

    public function test_construct_withInvalidFieldName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid field name 'invalid_field'");
        new PersonListModel($this->db_mock, ...['invalid_field__gt' => 0]);
    }


    // tests for delete

    public function test_delete()
    {
        $this->db_mock
            ->expects($this->once())
            ->method('deleteByIds')
            ->with(PersonModel::TABLE_NAME, 'person_id', ['1', '2']);
      
        $obj = $this->createObject($this->fields_1, $this->fields_2);
        $obj->delete();
    }


    // tests for getFields

    public function test_getFields()
    {
        $obg = $this->createObject($this->fields_1, $this->fields_2);
        $fields = $obg->getFields();
        $this->assertSame([$this->fields_1, $this->fields_2], $fields);
    }
}