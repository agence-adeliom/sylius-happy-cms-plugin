<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Entity\Channel;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * Logique commune du rattachement optionnel à des channels.
 *
 * Le mapping ORM n'est volontairement pas porté ici : chaque classe qui utilise ce trait
 * redéclare la propriété `$channels` avec son `#[ORM\ManyToMany]` et sa table de jointure
 * explicite, pour ne pas dépendre du nom de la classe concrète du projet.
 */
trait ChannelAwareTrait
{
    /** @var Collection<int, ChannelInterface> */
    protected Collection $channels;

    protected function initializeChannels(): void
    {
        $this->channels = new ArrayCollection();
    }

    /** @return Collection<int, ChannelInterface> */
    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(ChannelInterface $channel): void
    {
        if (!$this->channels->contains($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(ChannelInterface $channel): void
    {
        $this->channels->removeElement($channel);
    }

    public function hasChannel(ChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    public function isAvailableForAllChannels(): bool
    {
        return $this->channels->isEmpty();
    }
}
