<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<bool|string|null,string|null>
 * >
 *
 * Transforms between a Boolean and a string.
 *
 * Part modified in this file : line 56 > 61
 */
class BooleanToStringTransformer implements DataTransformerInterface
{
    /**
     * @param string $trueValue The value emitted upon transform if the input is true
     */
    public function __construct(
        private string $trueValue,
        private array $falseValues = [null],
    ) {
        if (\in_array($this->trueValue, $this->falseValues, true)) {
            throw new InvalidArgumentException('The specified "true" value is contained in the false-values.');
        }
    }

    /**
     * Transforms a Boolean into a string.
     *
     * @param bool|string|null $value Boolean value
     *
     * @throws TransformationFailedException if the given value is not a Boolean
     */
    public function transform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        // When a value is json decoded, a boolean value can be returned as a string
        // Ex "1", "0" , "true", "false"
        // is_bool() will return false
        if (\is_string($value)) {
            $value = filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        }

        if (!\is_bool($value)) {
            throw new TransformationFailedException('Expected a Boolean.');
        }

        return $value ? $this->trueValue : null;
    }

    /**
     * Transforms a string into a Boolean.
     *
     * @param string $value String value
     *
     * @throws TransformationFailedException if the given value is not a string
     */
    public function reverseTransform(mixed $value): bool
    {
        if (\in_array($value, $this->falseValues, true)) {
            return false;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return true;
    }
}
