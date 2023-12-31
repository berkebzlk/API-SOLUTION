<?php

require_once 'Core/functions.php';

/**
 * Class Validator
 *
 * A class for validating data based on specified rules.
 */
class Validator
{
    /**
     * @var array The data to be validated.
     */
    private $data;

    /**
     * @var array The validation rules.
     */
    private $rules;

    /**
     * @var array Holds validation errors.
     */
    private $errors = [];

    /**
     * Validator constructor.
     *
     * @param mixed $data The data to be validated.
     * @param array $rules The validation rules.
     */
    public function __construct($data, $rules)
    {
        $this->data = (array)$data;
        $this->rules = $rules;
    }

    /**
     * Validates the data based on the specified rules.
     *
     * @return bool Returns true if validation passes, false otherwise.
     */
    public function validate()
    {
        foreach ($this->rules as $field => $rule) {
            $rules = explode('|', $rule);
            foreach ($rules as $singleRule) {
                $this->applyRule($field, $singleRule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Gets the validation errors.
     *
     * @return array The validation errors.
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Applies a validation rule to a field.
     *
     * @param string $field The field to validate.
     * @param string $rule The validation rule.
     *
     * @throws Exception If the validation rule does not exist.
     */
    private function applyRule($field, $rule)
    {
        $params = explode(':', $rule);
        $ruleName = array_shift($params);

        $methodName = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $methodName)) {
            call_user_func([$this, $methodName], $field, $params);
        } else {
            throw new Exception("Validation rule '{$ruleName}' does not exist.");
        }
    }

    /**
     * Validates if the field value is in a list of allowed values.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateIn($field, $params)
    {
        list($allowedValues) = $params;

        $allowedValuesArray = explode(',', $allowedValues);

        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowedValuesArray)) {
            $this->addError($field, 'The ' . $field . ' field must be one of: ' . implode(', ', $allowedValuesArray), 'INVALID_VALUE');
        }
    }

    /**
     * Validates if the field value has a valid date format.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateDateFormat($field, $params)
    {
        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->data[$field]);

        if (!$date || $date->format('Y-m-d\TH:i:s\Z') !== $this->data[$field]) {
            $this->addError($field, 'The ' . $field . ' field must be in the ISO 8601 format: Y-m-d\TH:i:s\Z', 'INVALID_DATE_FORMAT');
        }
    }

    /**
     * Validates if the field value is after another field's value.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateAfter($field, $params)
    {
        list($otherField) = $params;

        $startDate = $this->data[$otherField];
        $endDate = $this->data[$field];

        if (strtotime($endDate) <= strtotime($startDate)) {
            $this->addError($field, "The $field must be after $otherField.");
        }
    }

    /**
     * Validates if the field has a default value if it is empty.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateDefault($field, $params)
    {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            if (isset($params[0])) {
                $this->data[$field] = $params[0];
            } else {
                $this->addError($field, 'The ' . $field . ' field must have a default value.', 'DEFAULT_VALUE_REQUIRED');
            }
        }
    }

    /**
     * Skips validation for a specific field.
     *
     * @param string $field The field to skip validation for.
     * @param array $params The parameters for the validation rule.
     */
    private function validateSkip($field, $params)
    {
        // Do nothing, validation is skipped for this field
    }

    /**
     * Validates if the field is required.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateRequired($field, $params)
    {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            $this->addError($field, 'The ' . $field . ' field is required.');
        }
    }

    /**
     * Validates if the field is nullable.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateNullable($field, $params)
    {
        if ($this->data[$field] === null || $this->data[$field] == "") {
            $this->removeError($field);
        }
    }

    /**
     * Validates if the field is a string.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateString($field, $params)
    {
        if (isset($this->data[$field]) && !is_string($this->data[$field])) {
            $this->addError($field, 'The ' . $field . ' field must be a string.');
        }
    }

    /**
     * Validates if the field length does not exceed a specified maximum.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateMax($field, $params)
    {
        list($max) = $params;

        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->addError($field, 'The ' . $field . ' field must not be greater than ' . $max . ' characters.');
        }
    }

    /**
     * Validates if the field has a valid HEX color code.
     *
     * @param string $field The field to validate.
     * @param array $params The parameters for the validation rule.
     */
    private function validateHexColor($field, $params)
    {
        if (isset($this->data[$field]) && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $this->data[$field])) {
            $this->addError($field, 'The ' . $field . ' field must be a valid HEX color code.');
        }
    }

    /**
     * Adds an error message for a specific field.
     *
     * @param string $field The field to add an error for.
     * @param string $message The error message.
     * @param string $code The error code.
     */
    private function addError($field, $message, $code = null)
    {
        $this->errors[$field][] = [
            'message' => $message,
            'code' => $code,
        ];
    }

    /**
     * Removes an error for a specific field.
     *
     * @param string $field The field to remove the error for.
     */
    private function removeError($field)
    {
        if (isset($this->errors[$field])) {
            unset($this->errors[$field]);
        }
    }
}
