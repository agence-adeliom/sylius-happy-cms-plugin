<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\DataMapperInterface;

use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigTranslationInterface;
use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ConfigTranslatableDataMapper implements DataMapperInterface
{
    public function __construct(
        private PropertyAccessor $propertyAccessor,
    ) {
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof ConfigInterface) {
            return;
        }

        $configType = $viewData->getType();

        $forms = iterator_to_array($forms);
        // on set les valeurs scalaires basiques (key, name, description, type)
        $forms['key']->setData($viewData->getKey());
        $forms['name']->setData($viewData->getName());
        $forms['description']->setData($viewData->getDescription());
        $forms['type']->setData($configType);

        // pour chaque type de champ translation (code, email, number, etc...) on met a jour les données existantes si elles existent sinon on met a null
        $typeKeys = array_values(ConfigTypeEnum::toArray());
        foreach ($typeKeys as $typeKey) {
            $forms[sprintf('translations_%s', $typeKey)]->setData($typeKey === $configType ? $viewData->getTranslations() : null);
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof ConfigInterface) {
            return;
        }

        $forms = iterator_to_array($forms);
        $configType = $forms['type']->getData();

        foreach ($forms as $key => $form) {
            // on set les valeurs scalaires basiques (key, name, description, type)
            if ($this->propertyAccessor->isWritable($viewData, $key)) {
                $this->propertyAccessor->setValue($viewData, $key, $form->getData());

                continue;
            }
            // si on est sur le bon type de champ (code, email, etc...) avec les translations
            if (is_string($configType) && $key === sprintf('translations_%s', $configType)) {
                /** @var array<string, ConfigTranslationInterface>|Collection<string, ConfigTranslationInterface> $newTranslations */
                $newTranslations = $form->getData();
                // si la donnée est l'ancienne translation elle est sous forme de collection doctrine, sinon la nouvelle donnée est un tableau simple
                /** @var array<string, ConfigTranslationInterface> $translationLocalesToKeep */
                $translationLocalesToKeep = $newTranslations instanceof Collection ? $newTranslations->toArray() : $newTranslations;
                foreach ($viewData->getTranslations() as $translation) {
                    if (!in_array($translation->getLocale(), array_keys($translationLocalesToKeep))) {
                        // si une ancienne locale n'est pas dans les données soumises on la supprime
                        $viewData->removeTranslation($translation);
                    } else {
                        // sinon on la met a jour directement (supprimer et ajouter pose des soucis d'unicité avec doctrine)
                        $locale = $translation->getLocale();
                        /** @var ConfigTranslationInterface $viewTranslation */
                        $viewTranslation = $viewData->getTranslation($locale);
                        $viewTranslation->setValue($newTranslations[$locale]->getValue());
                    }
                }
            }
        }
    }
}
