<?php

namespace Utopia\Validator;

use Utopia\Validator;

/**
 * Glob
 *
 * Validates a string against a list of gitignore-style glob patterns.
 * Supports * (single-segment wildcard), ** (multi-segment wildcard),
 * ? (single character that does not cross /), [abc] character classes,
 * \ escape sequences, and ! prefix for exclusions.
 *
 * Matching is case-sensitive. Inclusion patterns use OR semantics;
 * exclusion patterns use AND semantics. When both are present,
 * a specific inclusion overrides a wildcard exclusion.
 *
 * Pattern syntax follows the gitignore specification.
 * See https://git-scm.com/docs/gitignore for reference.
 */
class Glob extends Validator
{
    public function __construct(private readonly array $patterns)
    {
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Value must match a specific inclusion, or a wildcard inclusion not overridden by any exclusion.';
    }

    /**
     * Is valid
     *
     * Returns true if $value matches the configured glob patterns.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (empty($this->patterns)) {
            return true;
        }

        $include = array_filter($this->patterns, fn ($p) => !str_starts_with($p, '!'));
        $exclude = array_filter($this->patterns, fn ($p) => str_starts_with($p, '!'));

        if (empty($include)) {
            foreach ($exclude as $pattern) {
                if ($this->match($value, substr($pattern, 1))) {
                    return false;
                }
            }

            return true;
        }

        $isSpecific = fn ($pattern) => !str_contains($pattern, '*') && !str_contains($pattern, '?');

        foreach ($include as $pattern) {
            if ($isSpecific($pattern) && $this->match($value, $pattern)) {
                return true;
            }
        }

        foreach ($exclude as $pattern) {
            if ($this->match($value, substr($pattern, 1))) {
                return false;
            }
        }

        foreach ($include as $pattern) {
            if (!$isSpecific($pattern) && $this->match($value, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is array
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
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_STRING;
    }

    /**
     * Match a subject against a single pattern.
     * Uses fnmatch() for patterns without **, regex for globstar patterns.
     */
    private function match(string $subject, string $pattern): bool
    {
        if (!str_contains($pattern, '**')) {
            return fnmatch($pattern, $subject, FNM_PATHNAME);
        }

        return $this->matchGlobstar($subject, $pattern);
    }

    /**
     * Match using a regex built from a pattern that contains **.
     * Handles **, *, ?, [abc] character classes, and \ escape sequences.
     */
    private function matchGlobstar(string $subject, string $pattern): bool
    {
        $regex = '';
        $len = strlen($pattern);
        $i = 0;

        while ($i < $len) {
            $char = $pattern[$i];

            if ($char === '\\' && $i + 1 < $len) {
                $regex .= preg_quote($pattern[$i + 1], '~');
                $i += 2;
            } elseif ($char === '[') {
                $j = $i + 1;
                $bracketContent = '';

                // Allow ] as first char inside bracket (or after !)
                if ($j < $len && ($pattern[$j] === '!' || $pattern[$j] === '^')) {
                    $bracketContent .= $pattern[$j];
                    $j++;
                }
                if ($j < $len && $pattern[$j] === ']') {
                    $bracketContent .= ']';
                    $j++;
                }

                while ($j < $len && $pattern[$j] !== ']') {
                    $bracketContent .= $pattern[$j];
                    $j++;
                }

                if ($j < $len) {
                    // Well-formed [...] — normalise ! negation to ^
                    $inner = $bracketContent;
                    if (str_starts_with($inner, '!')) {
                        $inner = '^' . substr($inner, 1);
                    }
                    $regex .= '[' . $inner . ']';
                    $i = $j + 1;
                } else {
                    // Unclosed bracket — treat [ as a literal character
                    $regex .= preg_quote('[', '~');
                    $i++;
                }
            } elseif ($char === '*' && isset($pattern[$i + 1]) && $pattern[$i + 1] === '*') {
                $prevSlash = $i === 0 || $pattern[$i - 1] === '/';
                $nextSlash = isset($pattern[$i + 2]) && $pattern[$i + 2] === '/';

                if ($prevSlash && $nextSlash) {
                    // a/**/b — zero or more intermediate directories
                    $regex .= '(?:.+/)?';
                    $i += 3;
                } else {
                    // foo/** or standalone ** — matches everything
                    $regex .= '.*';
                    $i += 2;
                }
            } elseif ($char === '*') {
                $regex .= '[^/]*';
                $i++;
            } elseif ($char === '?') {
                $regex .= '[^/]';
                $i++;
            } else {
                $regex .= preg_quote($char, '~');
                $i++;
            }
        }

        return (bool) preg_match('~^' . $regex . '$~', $subject);
    }
}
