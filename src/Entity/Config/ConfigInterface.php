<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Config;

use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;

interface ConfigInterface extends ResourceInterface, TranslatableInterface
{
    public function getType(): ?string;

    public function getKey(): ?string;

    public function setKey(?string $key): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getDescription(): ?string;

    public function setDescription(string $description): void;

    public function setType(mixed $type): void;

    /**
     * @return Collection<(int|string), TranslationInterface|ConfigTranslationInterface>
     */
    public function getTranslations(): Collection;
}
