<?php

namespace Core;

/**
 * Validation Class
 * 
 * Handles data validation with customizable rules and messages.
 * Similar to Laravel's validation functionality.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Validator
{
    /**
     * Data to validate
     * 
     * @var array
     */
    protected array $data;

    /**
     * Validation rules
     * 
     * @var array
     */
    protected array $rules;

    /**
     * Custom error messages
     * 
     * @var array
     */
    protected array $messages;

    /**
     * Validation errors
     * 
     * @var array
     */
    protected array $errors = [];

    /**
     * Validated data
     * 
     * @var array
     */
    protected array $validatedData = [];

    /**
     * Create new validator instance
     * 
     * @param array $data
     * @param array $rules
     * @param array $messages
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Validate data against rules
     * 
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->validatedData = [];

        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->validateField($field, $value, $rule);
            }

            // Add to validated data if no errors for this field
            if (!isset($this->errors[$field])) {
                $this->validatedData[$field] = $value;
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate single field against rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return void
     */
    protected function validateField(string $field, $value, string $rule): void
    {
        // Parse rule and parameters
        $ruleName = $rule;
        $parameters = [];

        if (strpos($rule, ':') !== false) {
            list($ruleName, $paramString) = explode(':', $rule, 2);
            $parameters = explode(',', $paramString);
        }

        // Skip validation if field is not required and empty
        if ($ruleName !== 'required' && $this->isEmpty($value)) {
            return;
        }

        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $passes = call_user_func([$this, $method], $field, $value, $parameters);

            if (!$passes) {
                $this->addError($field, $ruleName, $parameters);
            }
        }
    }

    /**
     * Check if value is empty
     * 
     * @param mixed $value
     * @return bool
     */
    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Add validation error
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError(string $field, string $rule, array $parameters = []): void
    {
        $message = $this->getMessage($field, $rule, $parameters);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get error message for field and rule
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function getMessage(string $field, string $rule, array $parameters = []): string
    {
        $key = "{$field}.{$rule}";

        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        return $this->getDefaultMessage($field, $rule, $parameters);
    }

    /**
     * Get default error message
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function getDefaultMessage(string $field, string $rule, array $parameters = []): string
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least " . ($parameters[0] ?? 0) . " characters.",
            'max' => "The {$field} may not be greater than " . ($parameters[0] ?? 0) . " characters.",
            'numeric' => "The {$field} must be a number.",
            'string' => "The {$field} must be a string.",
            'array' => "The {$field} must be an array.",
            'boolean' => "The {$field} must be true or false.",
            'url' => "The {$field} must be a valid URL.",
            'date' => "The {$field} must be a valid date.",
            'unique' => "The {$field} has already been taken.",
            'confirmed' => "The {$field} confirmation does not match.",
            'in' => "The selected {$field} is invalid.",
        ];

        return $messages[$rule] ?? "The {$field} is invalid.";
    }

    // Validation rules

    protected function validateRequired(string $field, $value, array $parameters): bool
    {
        return !$this->isEmpty($value);
    }

    protected function validateEmail(string $field, $value, array $parameters): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(string $field, $value, array $parameters): bool
    {
        $min = (int) ($parameters[0] ?? 0);

        if (is_string($value)) {
            return strlen($value) >= $min;
        }

        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    protected function validateMax(string $field, $value, array $parameters): bool
    {
        $max = (int) ($parameters[0] ?? 0);

        if (is_string($value)) {
            return strlen($value) <= $max;
        }

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    protected function validateNumeric(string $field, $value, array $parameters): bool
    {
        return is_numeric($value);
    }

    protected function validateString(string $field, $value, array $parameters): bool
    {
        return is_string($value);
    }

    protected function validateArray(string $field, $value, array $parameters): bool
    {
        return is_array($value);
    }

    protected function validateBoolean(string $field, $value, array $parameters): bool
    {
        return is_bool($value) || in_array($value, ['1', '0', 'true', 'false', 1, 0], true);
    }

    protected function validateUrl(string $field, $value, array $parameters): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateDate(string $field, $value, array $parameters): bool
    {
        return strtotime($value) !== false;
    }

    protected function validateConfirmed(string $field, $value, array $parameters): bool
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }

    protected function validateIn(string $field, $value, array $parameters): bool
    {
        return in_array($value, $parameters);
    }

    /**
     * Check if validation fails
     * 
     * @return bool
     */
    public function fails(): bool
    {
        if (empty($this->errors)) {
            $this->validate();
        }

        return !empty($this->errors);
    }

    /**
     * Check if validation passes
     * 
     * @return bool
     */
    public function passes(): bool
    {
        return !$this->fails();
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data
     * 
     * @return array
     */
    public function getValidatedData(): array
    {
        if (empty($this->validatedData)) {
            $this->validate();
        }

        return $this->validatedData;
    }

    /**
     * Get first error for field
     * 
     * @param string $field
     * @return string|null
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Static validation method
     * 
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return static
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new static($data, $rules, $messages);
    }
}
