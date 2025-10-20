<?php

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * Ensure at least one validator from a list passed the check
 *
 * @package Utopia\Validator
 */
class AnyOf extends Validator
{
    protected ?Validator $failedRule = null;

    /**
     * @param array<Validator> $validators
     */
    public function __construct(protected array $validators, protected string $type = self::TYPE_MIXED)
    {
    }

    /**
     * Get Validators
     *
     * Returns validators array
     *
     * @return array<Validator>
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription(): string
    {
        if (!(\is_null($this->failedRule))) {
            $description = $this->failedRule->getDescription();
        } else {
            $description = $this->validators[0]->getDescription();
        }

        return $description;
    }

    /**
     * Is valid
     *
     * Validation will pass when all rules are valid if only one of the rules is invalid validation will fail.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        foreach ($this->validators as $rule) {
            $valid = $rule->isValid($value);

            $this->failedRule = $rule;

            if ($valid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Is array
     *
     * Function will return true if object is array.
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return true;
    }
}
