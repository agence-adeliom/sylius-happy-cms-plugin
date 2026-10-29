<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;

interface ConfigTranslationInterface extends TranslationInterface, ResourceInterface
{
    public function setDate(?\DateTime $date): void;

    public function setValue(mixed $value): void;

    public function getValue(): mixed;

    /** @return array<string, string> */
    public function getChannelValues(): array;

    /** @param array<string, string> $values */
    public function setChannelValues(array $values): void;

    public function getChannelValue(string $channelCode): ?string;

    public function setChannelValue(string $channelCode, ?string $value): void;
}
