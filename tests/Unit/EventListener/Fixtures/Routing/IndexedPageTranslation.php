<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\EventListener\Fixtures\Routing;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;

#[ORM\Entity]
#[ORM\Table(name: 'indexed_page_translation')]
class IndexedPageTranslation implements TranslationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: IndexedPage::class, inversedBy: 'translations')]
    private ?IndexedPage $translatable = null;

    public function __construct(
        #[ORM\Column]
        private ?string $locale,
        // generated from the name when left empty, if Gedmo's sluggable listener is registered
        #[ORM\Column(nullable: true)]
        #[Gedmo\Slug(fields: ['name'])]
        private ?string $slug,
        #[ORM\Column]
        private string $name = 'Page',
    ) {
    }

    public function getTranslatable(): TranslatableInterface
    {
        assert(null !== $this->translatable);

        return $this->translatable;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        assert(null === $translatable || $translatable instanceof IndexedPage);
        $this->translatable = $translatable;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
