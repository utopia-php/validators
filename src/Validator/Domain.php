<?php

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * Domain
 *
 * Validate that an variable is a valid domain address
 *
 * @package Utopia\Validator
 */
class Domain extends Validator
{
    /**
     * Helper for creating domain restriction rule.
     * Such rules prevent validation from passing, so this behaves as deny-list.
     *
     * @param string $hostname A domain base, such as top-level domain or subdomain. Restriction is only applied if domain matches this hostname
     * @param int $levels Specify what level (top-level, subdomain, sub-subdomain, ..) domain must be. Example: "stage.appwrite.io" is level 3
     * @param array<string> $prefixDenyList Disallowed beginning of domain, useful for reserved behaviours, such as prefixing "branch-" for preview domains
     *
     */
    public static function createRestriction(string $hostname, ?int $levels = null, array $prefixDenyList = [])
    {
        return [
            'hostname' => $hostname,
            'levels' => $levels,
            'prefixDenyList' => $prefixDenyList,
        ];
    }

    /**
     * @param array<mixed> $restrictions Set of conditions that prevent validation from passing
     */
    public function __construct(protected array $restrictions = [])
    {
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
        return 'Value must be a valid domain';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid domain.
     *
     * Validates domain names against RFC 1034, RFC 1035, RFC 952, RFC 1123, RFC 2732, RFC 2181, and RFC 1123.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (empty($value)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        if (\filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        if (\str_ends_with($value, '.') || \str_ends_with($value, '-')) {
            return false;
        }

        foreach ($this->restrictions as $restriction) {
            $hostname = $restriction['hostname'];
            $levels = $restriction['levels'];
            $prefixDenyList = $restriction['prefixDenyList'];

            // Only apply restriction rules to relevant domains
            if (!\str_ends_with($value, $hostname)) {
                continue;
            }

            // Domain-level restriction
            if (!is_null($levels)) {
                $expectedPartsCount = $levels;
                $partsCount = \count(\explode('.', $value, $expectedPartsCount + 1));
                if ($partsCount !== $expectedPartsCount) {
                    return false;
                }
            }

            // Domain prefix (beginning) restriction
            if (!empty($prefixDenyList)) {
                foreach ($prefixDenyList as $deniedPrefix) {
                    if (\str_starts_with($value, $deniedPrefix)) {
                        return false;
                    }
                }
            }
        }

        return true;
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
        return self::TYPE_STRING;
    }
}
