<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($classNameDetail) && $classNameDetail instanceof ClassNameDetails &&
    isset($scope, $addRepo, $addTrans)
) {
    $addContentBlocks = $addContentBlocks ?? false;
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= Str::getNamespace($classNameDetail->getFullName()) ?>;

use Adeliom\SyliusHappyCMSPlugin\Entity\<?= $scope ?>\<?=
    $classNameDetail->getShortName() ?> as Base<?= $classNameDetail->getShortName() ?>;
<?php if ($addTrans === true) { ?>
use Adeliom\SyliusHappyCMSPlugin\Entity\<?= $scope ?>\<?=
    $classNameDetail->getShortName() ?>TranslationInterface;<?php } ?><?php if ($addRepo === true) { ?>

use <?= str_replace('Entity', 'Repository', Str::getNamespace($classNameDetail->getFullName())) ?>\<?= $classNameDetail->getShortName() ?>Repository;
<?php } ?><?php if ($addContentBlocks === true) { ?>

use Doctrine\Common\Collections\Collection;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
<?php } ?>

use Doctrine\ORM\Mapping as ORM;
<?php if ($addRepo === true) { ?>
#[ORM\Entity(repositoryClass: <?= $classNameDetail->getShortName() ?>Repository::class)]
<?php } else { ?>
#[ORM\Entity]
<?php } ?>
#[ORM\Table(name: 'sylius_happy_cms__<?= Str::asSnakeCase($classNameDetail->getShortName()) ?>')]
class <?= $classNameDetail->getShortName() ?> extends Base<?= $classNameDetail->getShortName() ?> {
<?php if ($addContentBlocks === true) { ?>
    /** @var Collection<int, <?= $classNameDetail->getShortName() ?>ContentBlock> */
    #[ORM\OneToMany(targetEntity: <?= $classNameDetail->getShortName() ?>ContentBlock::class, mappedBy: 'contentOwner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $contentBlocks;

    /**
     * @return class-string<ContentBlockInterface>
     */
    public static function getContentBlockClass(): string
    {
        return <?= $classNameDetail->getShortName() ?>ContentBlock::class;
    }
<?php } ?>
    protected function createTranslation(): <?= $classNameDetail->getShortName() ?>TranslationInterface
    {
        return new <?= $classNameDetail->getShortName() ?>Translation();
    }

    public static function getTranslationClass(): string
    {
        return <?= $classNameDetail->getShortName() ?>Translation::class;
    }
}
<?php } ?>
