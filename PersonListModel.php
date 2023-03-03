<?php

require_once('autoload.php');

if (!class_exists('PersonModel'))
{
    throw new Exception('Class "PersonModel" not found');
}

/**
 * Class for working with myltiple `PersonModel` objects.
 */
class PersonListModel
{
    protected array $objects = [];

    /**
     * Create new class instance and fetch the values from the database
     * by `conditions` to initialize model fields.
     * Each condition is a named argument in the format:`field_name__lookup`.
     * Lookups then are replaced to operators in the WHERE clause of the SELECT statment.
     * Possible `lookup` values:
     *  - `gt` replaced to `>`
     *  - `lt` replaced to `<`
     *  - `gte` replaced to `>=`
     *  - `lte` replaced to `<=`
     *  - `not` replaced to `!=`
     * If only field name is provided then `=` operator will be used in SQL query.
     * @param DatabaseConnection $db object to interact with the database.
     * @param $conditions named args to be used in the WHERE clause of SELECT statment.
     */
    function __construct(protected DatabaseConnection $db, ...$conditions)
    {
        $this->checkFields($conditions);
        $rows = $this->db->selectWhere(PersonModel::TABLE_NAME, $conditions);
        foreach ($rows as $row)
        {
            $this->objects[] = new PersonModel($db, ...$row, validate: false);
        }
    }

    /**
     * Delete all objects from the database.
     */
    public function delete()
    {
        foreach ($this->objects as $object)
        {
            $ids[] = $object->person_id;
        }
        $this->db->deleteByIds(PersonModel::TABLE_NAME, 'person_id', $ids);
    }

    /**
     * Return all model object fields as 2d array.
     */
    public function getFields(): array
    {
        foreach ($this->objects as $obj)
        {
            $fields[] = $obj->getFields();
        }
        return $fields;
    }

    /**
     * Return all objects. 
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    /**
     * Check that field names are valid. Throw an exception on failure.
     * @param array $conditions array where each element in the format:
     * `field_name__lookup => some_value`
     */
    protected function checkFields(array $conditions)
    {
        foreach ($conditions as $key => $value)
        {
            $field_name = explode('__', $key, 2)[0];
            if (!in_array($field_name, PersonModel::FIELD_NAMES))
            {
                throw new Exception("Invalid field name '$field_name'");
            }
        }
    }

} 