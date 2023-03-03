<?php

require_once('autoload.php');
require_once('exceptions.php');

/**
 * Class for working with 'people' table.
 */
class PersonModel
{
    const TABLE_NAME = 'people';
    const FIELD_NAMES = [
        'person_id',
        'first_name',
        'last_name',
        'birthdate',
        'gender',
        'town'
    ];

    /**
     * Init model field values.
     * @param DatabaseConnection $db object to interact with the database.
     * @param bool $validate If true the field validation is performed.
     */
    function __construct(
            protected DatabaseConnection $db,
            protected string $first_name,
            protected string $last_name,
            protected string|DateTime $birthdate,
            protected string|int|bool $gender,
            protected string $town,
            protected string|int|bool|null $person_id = null,
            bool $validate = true)
    {
        if ($validate)
        {
            $this->setPersonId($person_id);
            $this->setFirstName($first_name);
            $this->setLastName($last_name);
            $this->setBirthdate($birthdate);
            $this->setGender($gender);
            $this->setTown($town);
        }
    }

    /**
     * Create the class instance and init model fields by values
     * fetched from the database by `person_id`.
     * @param DatabaseConnection $db: object to interact with a database.
     * @param int|string $person_id A person id in the database table.
     */
    public static function fromDB(DatabaseConnection $db, int|string $person_id)
    {
        $fields = $db->select(self::TABLE_NAME, ['person_id' => $person_id])[0];
        return new static($db, ...$fields, validate: false);
    }

    /**
     * Save the model fields except `person_id` to the database. 
     */
    public function save()
    {
        foreach (self::FIELD_NAMES as $name)
        {
            if ($name !== 'person_id')
            {
                $fields[$name] = $this->$name;
            }
        }
        $this->db->insert(self::TABLE_NAME, $fields);
    }

    /**
     * Delete object from the database. If the object has already deleted then
     * `DatabaseError` will be thrown. 
     */
    public function delete()
    {
        if (!is_null($this->person_id))
        {
            $this->db->delete(self::TABLE_NAME, ['person_id' => $this->person_id]);
        }
    }

    /**
     * Validate `person_id` value and set it to the property.
     * `ValidationError` will be thrown on the validation failure. 
     */
    public function setPersonId(int | string | null $person_id): void
    {
        if (is_string($person_id))
        {
            Validators::validateNumeric($person_id, '"person_id" field');
        }
        $this->person_id = strval($person_id);
    }

    /**
     * Validate `first_name` value and set it to the property.
     * `ValidationError` will be thrown on the validation failure. 
     */
    public function setFirstName(string $first_name): void
    {
        $msg_prefix = '"first_name" field';
        Validators::validateMaxLength($first_name, 30, $msg_prefix);
        Validators::validateAlphabeic($first_name, $msg_prefix);
        $this->first_name = $first_name;
    }

    /**
     * Validate `last_name` value and set it to the property.
     * `ValidationError` will be thrown on the validation failure. 
     */
    public function setLastName(string $last_name): void
    {
        $msg_prefix = '"last_name" field';
        Validators::validateMaxLength($last_name, 30, $msg_prefix);
        Validators::validateAlphabeic($last_name, $msg_prefix);
        $this->last_name = $last_name;
    }

    /**
     * Validate `birthdate` value and set it to the property.
     * `ValidationError` will be thrown on the validation failure. 
     */
    public function setBirthdate(string|DateTime $birthdate): void
    {
        if (is_string($birthdate))
        {
            Validators::validateDatetime($birthdate, '"birthdate" field');
            $birthdate = date_create($birthdate);
        }
        $this->birthdate = $birthdate->format('Y-m-d');
    }

    /**
     * Validate `gender` value and set it to the property.
     * `ValidationError` will be thrown on the validation failure. 
     */
    public function setGender(string|int|bool $gender): void
    {
        Validators::validateBit($gender, '"gender" field');
        $this->gender = strval($gender);
    }

    /**
     * Validate `town` value and set it to the property.
     * `ValidationError` will be thrown on the validation failure. 
     */
    public function setTown(string $town)
    {
        Validators::validateMaxLength($town, 168, '"town" field');
        $this->town = $town;
    }

    /**
     * Get a property by the `name`.
     */
    public function __get(string $name)
    {
        return $this->$name;
    }

    /**
     * Get all the model fields as an array.
     * @return array: The model fields.
     */
    public function getFields(): array
    {
        foreach (self::FIELD_NAMES as $name)
        {
            $fields[] = $this->$name;
        }
        return $fields;
    }


    /**
     * Get a person age by the `birthdate`.
     * @param string|DateTime $birthdate date of birth.
     * @return int age.
     */
    public static function getAge(string|DateTime $birthdate): int
    {
        if (is_string($birthdate))
        {
            Validators::validateDatetime($birthdate, '"birthdate" argument');
            $birthdate = date_create($birthdate);
        }
        $interval = date_diff($birthdate, date_create());
        return intval($interval->format('%Y'));
    }

    /**
     * Get verbose gender by `gender`.
     * @param string|int|bool $gender Must be 0/1 or true/false.
     * @return string 'муж' if `gender` is 0/false, else 'жен'.
     */
    public static function getVerboseGender(string|int|bool $gender): string
    {
        Validators::validateBit($gender, '"gender" argument');
        return ($gender == 0) ? 'муж' : 'жен'; 
    }
    
}