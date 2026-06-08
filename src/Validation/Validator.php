<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Validation;

use function date_parse;
use function explode;
use function filter_var;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function mb_strlen;
use function preg_match;

use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_INT;

class Validator implements ValidatorInterface
{
    private array $errors = [];
    private array $data = [];

    public function validate(array $data, array $rules): array
    {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return $this->errors;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;

        match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'min' => $this->validateMin($field, $value, (int) $parameter),
            'max' => $this->validateMax($field, $value, (int) $parameter),
            'numeric' => $this->validateNumeric($field, $value),
            'integer' => $this->validateInteger($field, $value),
            'string' => $this->validateString($field, $value),
            'date' => $this->validateDate($field, $value),
            'phone' => $this->validatePhone($field, $value),
            'postal_code' => $this->validatePostalCode($field, $value),
            'regex' => $this->validateRegex($field, $value, $parameter),
            'in' => $this->validateIn($field, $value, $parameter),
            'confirmed' => $this->validateConfirmed($field, $value),
            'password' => $this->validatePassword($field, $value),
            default => null,
        };
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && (! is_array($value) || ! empty($value))) {
            return;
        }

        $this->addError($field, "{$field}は必須です");
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->addError($field, '有効なメールアドレスを入力してください');
    }

    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "{$field}は{$min}文字以上で入力してください");
        }

        if (! is_numeric($value) || $value >= $min) {
            return;
        }

        $this->addError($field, "{$field}は{$min}以上で入力してください");
    }

    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "{$field}は{$max}文字以下で入力してください");
        }

        if (! is_numeric($value) || $value <= $max) {
            return;
        }

        $this->addError($field, "{$field}は{$max}以下で入力してください");
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || is_numeric($value)) {
            return;
        }

        $this->addError($field, "{$field}は数値で入力してください");
    }

    private function validateInteger(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT)) {
            return;
        }

        $this->addError($field, "{$field}は整数で入力してください");
    }

    private function validateString(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || is_string($value)) {
            return;
        }

        $this->addError($field, "{$field}は文字列で入力してください");
    }

    private function validateDate(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $date = date_parse($value);
        if ($date['error_count'] <= 0 && $date['warning_count'] <= 0) {
            return;
        }

        $this->addError($field, "{$field}は有効な日付で入力してください");
    }

    private function validatePhone(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (preg_match('/^[\d\-+().\s]+$/', $value)) {
            return;
        }

        $this->addError($field, "{$field}は有効な電話番号で入力してください");
    }

    private function validatePostalCode(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (preg_match('/^\d{3}-?\d{4}$/', $value)) {
            return;
        }

        $this->addError($field, "{$field}は有効な郵便番号で入力してください (例: 123-4567)");
    }

    private function validateRegex(string $field, mixed $value, string|null $pattern): void
    {
        if ($value === null || $value === '' || $pattern === null) {
            return;
        }

        if (preg_match($pattern, $value)) {
            return;
        }

        $this->addError($field, "{$field}の形式が正しくありません");
    }

    private function validateIn(string $field, mixed $value, string|null $values): void
    {
        if ($value === null || $value === '' || $values === null) {
            return;
        }

        $allowedValues = explode(',', $values);
        if (in_array((string) $value, $allowedValues, true)) {
            return;
        }

        $this->addError($field, "{$field}は許可された値ではありません");
    }

    private function validateConfirmed(string $field, mixed $value): void
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;
        if ($value === $confirmValue) {
            return;
        }

        $this->addError($field, "{$field}が確認用の値と一致しません");
    }

    private function validatePassword(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (mb_strlen($value) < 8) {
            $this->addError($field, 'パスワードは8文字以上で入力してください');
        }

        if (! preg_match('/[a-zA-Z]/', $value)) {
            $this->addError($field, 'パスワードには英字を含めてください');
        }

        if (preg_match('/\d/', $value)) {
            return;
        }

        $this->addError($field, 'パスワードには数字を含めてください');
    }

    private function addError(string $field, string $message): void
    {
        if (! isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }
}
