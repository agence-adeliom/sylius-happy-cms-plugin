<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Entity\Channel;

use Adeliom\SyliusHappyCMSPlugin\Entity\Channel\ChannelAwareInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Channel\ChannelAwareTrait;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ChannelAwareTraitTest extends TestCase
{
    public function testNewObjectIsAvailableForAllChannels(): void
    {
        $subject = $this->createSubject();

        self::assertCount(0, $subject->getChannels());
        self::assertTrue($subject->isAvailableForAllChannels());
    }

    public function testAddChannelIsIdempotent(): void
    {
        $subject = $this->createSubject();
        $channel = $this->createMock(ChannelInterface::class);

        $subject->addChannel($channel);
        $subject->addChannel($channel);

        self::assertCount(1, $subject->getChannels());
        self::assertFalse($subject->isAvailableForAllChannels());
    }

    public function testRemoveChannelRestoresAllChannels(): void
    {
        $subject = $this->createSubject();
        $channel = $this->createMock(ChannelInterface::class);
        $subject->addChannel($channel);

        $subject->removeChannel($channel);

        self::assertTrue($subject->isAvailableForAllChannels());
    }

    public function testHasChannel(): void
    {
        $subject = $this->createSubject();
        $attached = $this->createMock(ChannelInterface::class);
        $other = $this->createMock(ChannelInterface::class);
        $subject->addChannel($attached);

        self::assertTrue($subject->hasChannel($attached));
        self::assertFalse($subject->hasChannel($other));
    }

    private function createSubject(): ChannelAwareInterface
    {
        return new class() implements ChannelAwareInterface {
            use ChannelAwareTrait;

            public function __construct()
            {
                $this->initializeChannels();
            }
        };
    }
}
