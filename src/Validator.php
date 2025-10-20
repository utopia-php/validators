<?php

namespace Utopia;

/**
 * Base validator contract for all concrete validators shipped with the package.
 */
abstract class Validator
{
    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'double'; /* gettype() returns 'double' for historical reasons */

    public const TYPE_STRING = 'string';

    public const TYPE_ARRAY = 'array';

    public const TYPE_OBJECT = 'object';

    public const TYPE_MIXED = 'mixed';

    /**
     * @var string
     */
    protected string $key = '';

    /**
     * @var bool
     */
    protected bool $required = true;

    /**
     * @var mixed
     */
    protected mixed $value = null;

    /**
     * @var bool
     */
    protected bool $hasValue = false;

    /**
     * @var mixed
     */
    protected mixed $default = null;

    /**
     * @var string|null
     */
    protected ?string $message = null;

    /**
     * Returns validator description.
     */
    abstract public function getDescription(): string;

    /**
     * Validate the provided value.
     */
    abstract public function isValid(mixed $value): bool;

    /**
     * Indicates whether validator expects an array input.
     */
    abstract public function isArray(): bool;

    /**
     * Returns the expected PHP type.
     */
    abstract public function getType(): string;

    /**
     * Retrieve the key associated with validation errors.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the key associated with validation errors.
     */
    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Define whether the value is required.
     */
    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Flag indicating whether the value is required.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Set default value to use when input is missing.
     */
    public function setDefault(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Retrieve default value.
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Persist the last validated value.
     */
    public function setValue(mixed $value): static
    {
        $this->storeValue($value);

        return $this;
    }

    /**
     * Retrieve the last validated value.
     */
    public function getValue(): mixed
    {
        return $this->hasValue ? $this->value : $this->default;
    }

    /**
     * Reset runtime state.
     */
    public function reset(): static
    {
        $this->clearValue();
        $this->message = null;

        return $this;
    }

    /**
     * Override the default validation failure message.
     */
    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Retrieve the validation failure message when available.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Allow validators to coerce successfully validated values.
     */
    public function coerce(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Persist the provided value internally.
     */
    protected function storeValue(mixed $value): void
    {
        $this->value = $value;
        $this->hasValue = true;
    }

    /**
     * Clear the persisted value.
     */
    protected function clearValue(): void
    {
        $this->value = null;
        $this->hasValue = false;
    }

    /**
     * Run validation and persist the resulting value on success.
     */
    public function validate(mixed $value): bool
    {
        if ($value === null) {
            if ($this->isValid(null)) {
                $this->storeValue($this->coerce(null));

                return true;
            }

            if (! $this->required) {
                $this->storeValue($this->default);

                return true;
            }

            $this->clearValue();

            return false;
        }

        if (! $this->isValid($value)) {
            $this->clearValue();

            return false;
        }

        $this->storeValue($this->coerce($value));

        return true;
    }
}
