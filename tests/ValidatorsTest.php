<?php
namespace Utils;

use PHPUnit\Framework\TestCase;

final class ValidatorsTest extends TestCase
{
    public function validValuesProvider(): array
    {
        return [
            'validateMaxLength' => [
                __NAMESPACE__ . '\Validators::validateMaxLength', '123', 'max_length' => 3,
            ],
            'validateMaxLength with empty sting' => [
                __NAMESPACE__ . '\Validators::validateMaxLength', '', 'max_length' => 0,
            ],

            'validateAlphabeic' => [__NAMESPACE__ . '\Validators::validateAlphabeic', 'Ab'],

            'validateNumeric' => [__NAMESPACE__ . '\Validators::validateNumeric', '123'],

            'validateDatetime' => [__NAMESPACE__ . '\Validators::validateDatetime', '2023-01-01'],
            'validateDatetime with now' => [__NAMESPACE__ . '\Validators::validateDatetime', 'now'],

            'validateBit with string' => [__NAMESPACE__ . '\Validators::validateBit', '0'],
            'validateBit with int' => [__NAMESPACE__ . '\Validators::validateBit', 1],
            'validateBit wint bool' => [__NAMESPACE__ . '\Validators::validateBit', true],
        ];
    }


    public function invalidValuesProvider(): array
    {
        return [
            'validateMaxLength' => [__NAMESPACE__ . '\Validators::validateMaxLength', '1234', 'max_length' => 3],
            'validateMaxLength with negative max_length' => [
                __NAMESPACE__ . '\Validators::validateMaxLength', '123', 'max_length' => -1
            ],

            'validateAlphabeic with digit' => [__NAMESPACE__ . '\Validators::validateAlphabeic', 'a1'],
            'validateAlphabeic with special symbol' => [__NAMESPACE__ . '\Validators::validateAlphabeic', 'a!'],
            'validateAlphabeic with empty string' => [__NAMESPACE__ . '\Validators::validateAlphabeic', ''],
            
            'validateNumeric with non-numeric symbol' => [__NAMESPACE__ . '\Validators::validateNumeric', '1a'],
            'validateNumeric with negative number' => [__NAMESPACE__ . '\Validators::validateNumeric', '-1'],
            'validateNumeric with empty string' => [__NAMESPACE__ . '\Validators::validateNumeric', ''],
            
            'validateDatetime with invalid values' => [__NAMESPACE__ . '\Validators::validateDatetime', '2023-99-99'],
            'validateDatetime with invalid format' => [__NAMESPACE__ . '\Validators::validateDatetime', '2023_01_01'],
            'validateDatetime with arbitary string' => [__NAMESPACE__ . '\Validators::validateDatetime', 'some_str'],

            'validateBit with 2' => [__NAMESPACE__ . '\Validators::validateBit', 2],
            'validateBit with -1' => [__NAMESPACE__ . '\Validators::validateBit', -1],
            'validateBit with invalid string' => [__NAMESPACE__ . '\Validators::validateBit', 'a'],
            'validateBit with empty string' => [__NAMESPACE__ . '\Validators::validateBit', ''],
        ];
    } 

    public function errorMessagesProvider()
    {
        return [
            'validateMaxLength' => [
                __NAMESPACE__ . '\Validators::validateMaxLength',
                '123',
                'The value 123 has length greater that 2',
                'max_length' => 2,
            ],
            'validateAlphabeic' => [
                __NAMESPACE__ . '\Validators::validateAlphabeic',
                '123',
                'The value 123 contains non-alphabetic charachers'
            ],
            'validateNumeric' => [
                __NAMESPACE__ . '\Validators::validateNumeric',
                'abc',
                'The value abc contains non-numeric charachers'
            ],
            'validateDatetime' => [
                __NAMESPACE__ . '\Validators::validateDatetime',
                'invalid_value',
                'The value invalid_value cannot be interpreted as datetime'
            ],
            'validateBit' => [
                __NAMESPACE__ . '\Validators::validateBit',
                'invalid_value',
                'The value invalid_value cannot be interpreted as bit'
            ],
        ];
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidatorWithValidValue(callable $validator, $valid_value, ...$extra)
    {
        $validator($valid_value, ...$extra);
        $this->assertTrue(true);
    }

    /**
     * @dataProvider invalidValuesProvider
     */
    public function testValidatorWithInvalidValue(callable $validator, $invalid_value, ...$extra)
    {
        $this->expectException(ValidationError::class);
        $validator($invalid_value, ...$extra);
    }

    /**
     * @dataProvider errorMessagesProvider
     */
    public function testErrorMessage(callable $validator, $invalid_value, $error_msg, ...$extra)
    {
        $this->expectErrorMessage($error_msg);
        $validator($invalid_value, ...$extra);
    }

    /**
     * @dataProvider errorMessagesProvider
     */
    public function testErrorMessageWithPrefix(callable $validator, $invalid_value, $error_msg, ...$extra)
    {
        $this->expectErrorMessage('Prefix: ' . $error_msg);
        $extra['err_prefix'] = 'Prefix';
        $validator($invalid_value, ...$extra);
    }
}
