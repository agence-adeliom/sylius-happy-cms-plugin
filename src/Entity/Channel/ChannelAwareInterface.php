<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Channel;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;

interface ChannelAwareInterface
{
    /** @return Collection<int, ChannelInterface> */
    public function getChannels(): Collection;

    public function addChannel(ChannelInterface $channel): void;

    public function removeChannel(ChannelInterface $channel): void;

    public function hasChannel(ChannelInterface $channel): bool;

    /**
     * Une relation vide signifie « disponible sur tous les channels ».
     */
    public function isAvailableForAllChannels(): bool;
}
