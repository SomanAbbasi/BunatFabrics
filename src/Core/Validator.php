<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small rule based validator used by the auth forms.
 *
 * Rules are provided as a pipe separated string, e.g.
 *   'name'  => 'required|min:2|max:100',
 *   'email' => 'required|email|max:190',
 *   'password' => 'required|password|confirmed',
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * Run the rules and return a map of field => first error message.
     *
     * @param array<string, string> $rules
     * @param array<string, string> $labels
     * @return array<string, string>
     */
    public function validate(array $rules, array $labels = []): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $this->data[$field] ?? null;
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

            foreach (explode('|', $ruleSet) as $rule) {
                $this->applyRule($field, $label, $value, $rule);
            }
        }

        return $this->errors;
    }

    private function applyRule(string $field, string $label, mixed $value, string $rule): void
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        if (isset($this->errors[$field])) {
            return;
        }

        $value = is_string($value) ? trim($value) : $value;

        match ($name) {
            'required'  => $this->require($field, $label, $value),
            'email'     => $this->email($field, $label, $value),
            'phone'     => $this->phone($field, $label, $value),
            'min'       => $this->length($field, $label, $value, (int) $parameter, 'min'),
            'max'       => $this->length($field, $label, $value, (int) $parameter, 'max'),
            'password'  => $this->password($field, $label, $value),
            'confirmed' => $this->confirmed($field, $label, $value),
            default     => null,
        };
    }

    private function require(string $field, string $label, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->errors[$field] = "{$label} is required.";
        }
    }

    private function email(string $field, string $label, mixed $value): void
    {
        if ($value !== null && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email address.";
        }
    }

    private function phone(string $field, string $label, mixed $value): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (! preg_match('/^\+?[0-9][0-9\s().-]{6,19}$/', trim($value)) || strlen($digits) < 7 || strlen($digits) > 15) {
            $this->errors[$field] = "{$label} must be a valid phone number.";
        }
    }

    private function length(string $field, string $label, mixed $value, int $limit, string $mode): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $length = mb_strlen($value);

        if ($mode === 'min' && $length < $limit) {
            $this->errors[$field] = "{$label} must be at least {$limit} characters.";
        }

        if ($mode === 'max' && $length > $limit) {
            $this->errors[$field] = "{$label} may not exceed {$limit} characters.";
        }
    }

    /**
     * Enforce the shared password policy: 8+ chars with upper, lower and digit.
     */
    private function password(string $field, string $label, mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (mb_strlen($value) < 8
            || ! preg_match('/[A-Z]/', $value)
            || ! preg_match('/[a-z]/', $value)
            || ! preg_match('/\d/', $value)
        ) {
            $this->errors[$field] =
                "{$label} must be 8+ characters and include an uppercase letter, a lowercase letter and a number.";
        }
    }

    private function confirmed(string $field, string $label, mixed $value): void
    {
        if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
            $this->errors[$field] = "{$label} confirmation does not match.";
        }
    }
}
