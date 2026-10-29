<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Entity\Config;

use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\ConfigTranslation;

final class ConfigTranslationChannelFieldAccessTest extends TestCase
{
    public function testMediaIdIsStoredAsString(): void
    {
        $translation = $this->createTranslation('image');

        $translation->{'channel-FR_WEB'} = 42;

        self::assertSame('42', $translation->getChannelValue('FR_WEB'));
    }

    public function testDateTimeIsFormattedAccordingToTheConfigType(): void
    {
        $date = new \DateTime('2026-10-29 13:30:15');

        foreach (['date' => '2026-10-29', 'time' => '13:30:15', 'datetime' => '2026-10-29 13:30:15'] as $type => $expected) {
            $translation = $this->createTranslation($type);
            $translation->{'channel-FR_WEB'} = $date;

            self::assertSame($expected, $translation->getChannelValue('FR_WEB'), $type);
        }
    }

    public function testZeroStringIsAKeptOverride(): void
    {
        $translation = $this->createTranslation('boolean');

        $translation->{'channel-FR_WEB'} = '0';

        self::assertSame('0', $translation->getChannelValue('FR_WEB'));
    }

    public function testNullRemovesTheOverride(): void
    {
        $translation = $this->createTranslation('text');
        $translation->setChannelValue('FR_WEB', 'x');

        $translation->{'channel-FR_WEB'} = null;

        self::assertSame([], $translation->getChannelValues());
    }

    private function createTranslation(string $type): ConfigTranslation
    {
        $config = new Config();
        $config->setKey('key');
        $config->setType($type);
        $translation = new ConfigTranslation();
        $translation->setLocale('fr_FR');
        $config->addTranslation($translation);

        return $translation;
    }
}
