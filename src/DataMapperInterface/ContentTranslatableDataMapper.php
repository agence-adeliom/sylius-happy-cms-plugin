<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DataMapperInterface;

use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ContentTranslatableDataMapper implements DataMapperInterface
{
    private string $locale;

    public function __construct(
        private string $class,
        protected LocaleProviderInterface $localeProvider,
        protected PropertyAccessor $propertyAccessor,
        ?string $context = null,
    ) {
        // get the current locale based on context, or fallback on default locale
        $defaultLocale = $this->localeProvider->getDefaultLocaleCode();
        if (!$context) {
            $this->locale = $defaultLocale;
        } else {
            $this->locale = array_values(array_filter(
                $this->localeProvider->getAvailableLocalesCodes(),
                static fn (string $locale): bool => str_ends_with($context, $locale),
            ))[0] ?? $defaultLocale;
        }
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms)
    {
        if (!(($viewData::class === $this->class || is_subclass_of($viewData, $this->class)) && $viewData instanceof TranslatableInterface)) {
            return;
        }

        $forms = iterator_to_array($forms);
        $translation = $viewData->getTranslation($this->locale);
        $reflectionClass = new \ReflectionClass($translation);
        $translationProperties = $reflectionClass->getProperties();
        foreach ($translationProperties as $property) {
            if (isset($forms[$property->getName()])) {
                $forms[$property->getName()]->setData($property->getValue($translation));
            }
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData)
    {
        if (!(($viewData::class === $this->class || is_subclass_of($viewData, $this->class)) && $viewData instanceof TranslatableInterface)) {
            return;
        }

        $translation = $viewData->getTranslation($this->locale);
        $forms = iterator_to_array($forms);
        foreach ($forms as $form) {
            /** @var \Symfony\Component\Form\Form $form */
            $this->propertyAccessor->setValue($translation, $form->getName(), $form->getData());
        }
    }
}
