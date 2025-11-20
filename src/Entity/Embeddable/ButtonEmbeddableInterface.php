<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Embeddable;

interface ButtonEmbeddableInterface extends \Stringable
{
    /**
     * @return array{label: string|null, link: string|null, icon: string|null, action: string|null}
     */
    public function toArray(): array;
}
