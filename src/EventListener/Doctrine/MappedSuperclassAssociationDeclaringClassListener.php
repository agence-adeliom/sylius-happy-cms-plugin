<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Gives back to the associations inherited from a plugin mapped superclass the class that declares them.
 *
 * The plugin models (Page, Menu, MenuItem, Folder…) are Sylius resources. For such a mapped superclass, Sylius
 * `ORMMappedSuperClassSubscriber` removes the one-to-many, many-to-many and one-to-one associations from its metadata.
 * The attribute driver then reads them again on the first entity extending the model, as if that entity declared
 * them. An entity below it (single table or joined inheritance, e.g. `LandingPage extends Page extends <plugin Page>`)
 * then sees the inherited property as a new declaration and Doctrine throws
 * "Property "routes" in "LandingPage" was already declared, but it must be declared only once".
 *
 * Marking these associations as declared by the plugin class, as Doctrine does when the mapped superclass keeps them,
 * lets the subclasses skip them. Only the entities mapped with an inheritance (single table or joined) are changed,
 * and only their associations without a declaring class whose property belongs to a plugin class: the metadata of an
 * entity without inheritance (the usual project entity extending a plugin model) is left untouched.
 */
final class MappedSuperclassAssociationDeclaringClassListener
{
    private const PLUGIN_NAMESPACE = 'Adeliom\\SyliusHappyCMSPlugin\\';

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();
        if ($metadata->isMappedSuperclass || $metadata->isEmbeddedClass || $metadata->isInheritanceTypeNone()) {
            return;
        }

        $class = $metadata->getReflectionClass();
        foreach ($metadata->associationMappings as $field => $mapping) {
            if (null !== $mapping->declared || !$class->hasProperty($field)) {
                continue;
            }

            $declaringClass = $class->getProperty($field)->class;
            if ($declaringClass !== $metadata->getName() && str_starts_with($declaringClass, self::PLUGIN_NAMESPACE)) {
                $mapping->declared = $declaringClass;
            }
        }
    }
}
