<?php
declare(strict_types=1);

namespace App\Services;

final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        if (trim((string) ($this->data[$field] ?? '')) === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Enter a valid email address.';
        }
        return $this;
    }

    public function max(string $field, int $length, string $label): self
    {
        if (mb_strlen((string) ($this->data[$field] ?? '')) > $length) {
            $this->errors[$field] = "{$label} must be {$length} characters or fewer.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "Choose a valid {$label}.";
        }
        return $this;
    }

    public function date(string $field): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && strtotime($value) === false) {
            $this->errors[$field] = 'Enter a valid date.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return (string) (array_values($this->errors)[0] ?? '');
    }
}
