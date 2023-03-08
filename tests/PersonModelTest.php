<?php
namespace Models;

use \PHPUnit\Framework\TestCase;
use \Utils\ValidationError;
use \Utils\DoesNotExistsError;


final class PersonModelTest extends TestCase
{
    public $db_mock;

    public $common_fields;


    public function setUp(): void
    {
        $this->db_mock = $this->createMock(\Database\DatabaseConnection::class);
        $this->common_fields = [
            'first_name' => 'first', 
            'last_name' => 'last', 
            'birthdate' => '2023-01-01', 
            'gender' => '1', 
            'town' => 'town', 
            'person_id' => '1'
        ];
    }


    // tests for construct

    public function validArgsProvider()
    {
        return [
            'common case' => ['first', 'last', '2023-01-01', '1', 'town', '1'],
            'with time in datatime' => ['first', 'last', '2023-01-01 00:00:00', '1', 'town', '1'],
            'with datetime object' => ['first', 'last', date_create('2023-01-01'), '1', 'town', '1'],
            'with gender as int' => ['first', 'last', '2023-01-01', 1, 'town', '1'],
            'with gender as bool' => ['first', 'last', '2023-01-01', true, 'town', '1'],
            'with person_id as int' => ['first', 'last', '2023-01-01', '1', 'town', 1],
        ];
    }

    public function invalidArgsProvider()
    {
        return [
            'with invalid first name' => ['123', 'last', '2023-01-01', '1', 'town', '1'],
            'with invalid last name' => ['first', '123', '2023-01-01', '1', 'town', '1'],
            'with invalid birthdate' => ['first', 'last', 'invalid_date', '1', 'town', '1'],
            'with invalid gender' => ['first', 'last', '2023-01-01', 'invalid_gender', 'town', '1'],
            'with invalid town' => ['first', 'last', '2023-01-01', '1', str_repeat('a', 200), '1'],
            'with invalid person id' => ['first', 'last', '2023-01-01', '1', 'town', 'invalid_id'],
        ];
    }

     /**
     * @dataProvider validArgsProvider
     */
    public function test_construct($first_name, $last_name, $birthdate, $gender, $town, $person_id)
    {
        $obj = new PersonModel($this->db_mock, $first_name, $last_name, $birthdate, $gender, $town, $person_id);
        $this->assertSame($obj->first_name, 'first');
        $this->assertSame($obj->last_name, 'last');
        $this->assertSame($obj->birthdate, '2023-01-01');
        $this->assertSame($obj->gender, '1');
        $this->assertSame($obj->town, 'town');
        $this->assertSame($obj->person_id, '1');
    }

    /**
     * @dataProvider invalidArgsProvider
     */
    public function test_construct_withInvalidArgs($first_name, $last_name, $birthdate, $gender, $town, $person_id)
    {
        $this->expectException(ValidationError::class);
        new PersonModel($this->db_mock, $first_name, $last_name, $birthdate, $gender, $town, $person_id);
    }

    public function test_construct_withoutPersonId()
    {
        unset($this->common_fields['person_id']);
        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $this->assertNull($obj->person_id);
    }

    public function test_construct_withDisabledValidation()
    {
        $obj = new PersonModel($this->db_mock, '123', '123', 'inv', 'inv', 
            'inv', 'inv', validate: false);

        $this->assertSame($obj->first_name, '123');
        $this->assertSame($obj->last_name, '123');
        $this->assertSame($obj->birthdate, 'inv');
        $this->assertSame($obj->gender, 'inv');
        $this->assertSame($obj->town, 'inv');
        $this->assertSame($obj->person_id, 'inv');
    }


    // tests for fromDB

    public function personIdProvider()
    {
        return [
            'with id as string' => ['1'],
            'with id as int' => [1],
        ];
    }


    /**
     * @dataProvider personIdProvider
     */
    public function test_fromDB($person_id)
    {
        $this->db_mock
            ->expects($this->once())
            ->method('select')
            ->with(PersonModel::TABLE_NAME, ['person_id' => $person_id])
            ->willReturn([$this->common_fields]);

        PersonModel::fromDB($this->db_mock, '1');
    }

    public function test_formDB_withInvalidId()
    {
        $this->expectException(ValidationError::class);
        PersonModel::fromDB($this->db_mock, 'invalid_id');
    }

    public function test_fromDB_withNonexistentId()
    {
        $table_name = PersonModel::TABLE_NAME;
        $this->expectException(DoesNotExistsError::class);
        $this->expectExceptionMessage("Record with id '999' does not exists in '$table_name' table");
        PersonModel::fromDB($this->db_mock, '999');
    }


    // tests for save

    public function test_save()
    {
        $common_fields = array_diff_key($this->common_fields, ['person_id' => null]);
        $this->db_mock
        ->expects($this->once())
        ->method('insert')
        ->with(PersonModel::TABLE_NAME, $common_fields);

        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $obj->save();
    }


    // tests for delete

    public function test_delete()
    {
        $this->db_mock
            ->expects($this->once())
            ->method('delete')
            ->with(PersonModel::TABLE_NAME, ['person_id' => '1']);

        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $obj->delete();
    }

    public function test_delete_withoutId()
    {
        $this->db_mock
            ->expects($this->never())
            ->method('delete');

        unset($this->common_fields['person_id']);
        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $obj->delete();
    }


    // tests for formatFields

    public function test_formatFields_withAllTrue()
    {
        $this->common_fields['birthdate'] = '2000-01-01';
        $this->common_fields['gender'] = '1';
        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        
        $new_obj = $obj->formatFields(true, true);

        $this->assertSame($new_obj->birthdate, 23);
        $this->assertSame($new_obj->gender, 'жен');
        return $new_obj;
    }

    /**
     * @depends test_formatFields_withAllTrue
     */
    public function test_formatFields_withAllFalse($obj)
    {   
        $new_obj = $obj->formatFields(false, false);
        $this->assertSame($new_obj->birthdate, '2000-01-01');
        $this->assertSame($new_obj->gender, '1');
        return $new_obj;
    }


    // tests for getFields

    public function test_getFields()
    {
        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $fields = $obj->getFields();
        $this->assertEqualsCanonicalizing($fields, $this->common_fields);
    }


    // test for getAge

    public function datesProvider()
    {
        return [
            'with date as string' => ['2000-01-01', 23],
            'with date as datetime obj' => [date_create('2000-01-01'), 23],
            'with future date' => ['2031-01-01', -7],
        ];
    }

    /**
     * @dataProvider datesProvider
     */
    public function test_getAge($birthdate, $expected_age)
    {
        $age = PersonModel::getAge($birthdate);
        $this->assertSame($age, $expected_age);
    }

    public function test_getAge_withInvalidValue()
    {
        $this->expectException(ValidationError::class);
        PersonModel::getAge('invalid_date');
    }


    // test for getVerboseGender

    public function binaryProvider()
    {
        return [
            'as string' => ['1', 'жен'],
            'as int' => [0, 'муж'],
            'with bool' => [true, 'жен'],
        ];
    }

    /**
     * @dataProvider binaryProvider
     */
    public function test_getVerboseGender($gender, $expected_gender)
    {
        $gender = PersonModel::getVerboseGender($gender);
        $this->assertSame($gender, $expected_gender);
    }

    public function test_getVeboseGender_withInvalidValue()
    {
        $this->expectException(ValidationError::class);
        PersonModel::getVerboseGender('invalid_value');
    }


    // test for setters

    public function idProvider()
    {
        return [
            'as string' => ['2', '2'],
            'as int' => [2, '2'],
            'as empty stirng' => ['', null],
            'as null' => [null, null],
        ];
    }


    public function validSetterValuesProvider()
    {
        // Each array in the format: [<setter_name>, <valid_setter_argument>, 
        //                            <property_name> <expected_property_value>]
        return [
            'id as string' => ['setPersonId', '2', 'person_id', '2'],
            'id as negative int' => ['setPersonId', -2, 'person_id', '-2'],
            'id as empty string' => ['setPersonId', '', 'person_id', null],
            'id as null' => ['setPersonId', null, 'person_id', null],

            'first_name as string' => ['setFirstName', 'name', 'first_name', 'name'],

            'last_name as string' => ['setLastName', 'name', 'last_name', 'name'],

            'birthdate as sting' => ['setBirthdate', '2023-01-01', 'birthdate', '2023-01-01'],
            'birthdate as datetime obj' => ['setBirthdate', date_create('2023-01-01'), 'birthdate', '2023-01-01'],
            'birthdate with time' => ['setBirthdate', '2023-01-01 00:01:02', 'birthdate', '2023-01-01'],

            'gender as string' => ['setGender', '1', 'gender', '1'],
            'gender as int' => ['setGender', 0, 'gender', '0'],
            'gender as bool' => ['setGender', true, 'gender', '1'],

            'town as string' => ['setTown', 'town_name_123', 'town', 'town_name_123'],
        ];
    }

    public function invalidSetterValuesProvider()
    {
        // Each array in the format: [<setter_name>, <invalid_setter_argument>, <property_name>]
        return [
            'id with non-digit chars' => ['setPersonId', 'invalid', 'person_id'],

            'first_name with non-alphabetic chars' => ['setFirstName', '_invlid', 'first_name'],
            'first_name with too long string' => ['setFirstName', str_repeat('a', 31), 'first_name'],
            'first_name  with empty string' => ['setFirstName', '', 'first_name'],

            'last_name as string' => ['setLastName', '_invlid', 'last_name'],
            'last_name with too long string' => ['setFirstName', str_repeat('a', 31), 'last_name'],
            'last_name with empty string' => ['setFirstName', '', 'last_name'],

            'birthdate with invalid values' => ['setBirthdate', '2023-99-99', 'birthdate'],
            'birthdate with invalid format' => ['setBirthdate', '2023_01_01', 'birthdate'],

            'gender not in (0, 1)' => ['setGender', '2', 'gender'],

            'town with too long string' => ['setTown', str_repeat('a', 169), 'town'],
        ];
    }

    /**
     * @dataProvider validSetterValuesProvider
     */
    public function test_setter_withValidArg($setter_name, $valid_argument, 
            $propery_name, $expected_property_value)
    {
        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $obj->$setter_name($valid_argument);
        $this->assertSame($obj->$propery_name, $expected_property_value);
    }

    /**
     * @dataProvider invalidSetterValuesProvider
     */
    public function test_setter_withInvalidArg($setter_name, $invalid_argument, $propery_name)
    {
        $obj = new PersonModel($this->db_mock, ...$this->common_fields);
        $this->expectException(ValidationError::class);
        $obj->$setter_name($invalid_argument);
    }
}