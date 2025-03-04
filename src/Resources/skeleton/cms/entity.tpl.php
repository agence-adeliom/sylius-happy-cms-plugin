<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($classNameDetail) && $classNameDetail instanceof ClassNameDetails &&
    isset($relationClassNameDetail, $scope,$addRepo,$addTrans,$isOwningSide,$hasRouting)
) {
    $mainClassData = [
        'singular' => mb_strtolower(Str::asSnakeCase($classNameDetail->getShortName())),
        'plural' => mb_strtolower(Str::asSnakeCase(Str::singularCamelCaseToPluralCamelCase($classNameDetail->getShortName()))),
    ];
    if ($relationClassNameDetail instanceof ClassNameDetails) {
        $relationClassData = [
            'singular' => mb_strtolower(Str::asSnakeCase($relationClassNameDetail->getShortName())),
            'plural' => mb_strtolower(Str::asSnakeCase(Str::singularCamelCaseToPluralCamelCase($relationClassNameDetail->getShortName()))),
        ];
    }
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= Str::getNamespace($classNameDetail->getFullName()) ?>;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait;
#use Adeliom\SyliusEasyCrudPlugin\Traits\EntityStatusTrait;
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;
<?php if ($addRepo === true) { ?>

use <?= str_replace('Entity', 'Repository', Str::getNamespace($classNameDetail->getFullName())) ?>\<?= $classNameDetail->getShortName() ?>Repository;
<?php } ?>
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use JMS\Serializer\Annotation as Serializer;
 use Symfony\Component\Validator\Constraints as Assert;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;
<?php if (true === $hasRouting) { ?>
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityRouteTrait;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as OrmRoute;
<?php } ?>

#[ORM\HasLifecycleCallbacks]
<?php if ($addRepo === true) { ?>
#[ORM\Entity(repositoryClass: <?= $classNameDetail->getShortName() ?>Repository::class)]
<?php } else { ?>
#[ORM\Entity]
<?php } ?>
#[ORM\Table(name: 'sylius_happy_cms__<?= Str::asSnakeCase($classNameDetail->getShortName()) ?>')]
#[ORM\Index(columns: ['publishState'], name: '<?= mb_strtolower($scope) ?>__<?= $mainClassData['singular'] ?>_indexes')]
#[Serializer\ExclusionPolicy('ALL')]
class <?= $classNameDetail->getShortName() ?> implements ResourceInterface, TranslatableInterface<?= $hasRouting ? ', CmsRoutableInterface ' : ' ' ?>
{
    use TranslatableTrait {
        TranslatableTrait::__construct as private _initializeTranslationsCollection;
        getTranslation as private _doGetTranslation;
    }
    use EntityTimestampableTrait {
        EntityTimestampableTrait::__construct as private _timestampableConstruct;
    }
    use EntityPublishableTrait {
        EntityPublishableTrait::__construct as private _publishableConstruct;
    }
<?php if (true === $hasRouting) { ?>
    use EntityRouteTrait {
        EntityRouteTrait::__construct as private _entityRouteConstruct;
    }
<?php } ?>

    use EntityIdTrait;
<?php if ($relationClassNameDetail instanceof ClassNameDetails) { ?>
    #[ORM\ManyToMany(
        targetEntity: <?= $relationClassNameDetail->getShortName() ?>::class,
        <?= $isOwningSide ? 'inversedBy:' : 'mappedBy:' ?> '<?= $mainClassData['plural'] ?>'
    )]
<?php if (true === $isOwningSide) { ?>
    #[ORM\JoinTable(name: 'happy_cms_<?= mb_strtolower($scope) ?>__<?= $relationClassData['singular'] ?>_<?= $mainClassData['singular'] ?>')]
<?php } ?>
    protected Collection $<?= $relationClassData['plural'] ?>;
<?php } ?>

<?php if (true === $hasRouting) { ?>
    /** @var Collection<int, OrmRoute> */
    #[ORM\ManyToMany(targetEntity: OrmRoute::class, cascade: ["persist", "remove"])]
    #[ORM\JoinTable('happy_cms_<?= mb_strtolower($scope) ?>__<?= $mainClassData['singular'] ?>_route')]
    protected Collection $routes;
<?php } ?>

    #[ORM\Column(name: 'css', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $css = null;

    #[ORM\Column(name: 'js', type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    protected ?string $js = null;

    public function __construct()
    {
        $this->_initializeTranslationsCollection();
        $this->_publishableConstruct();
        $this->_timestampableConstruct();
    <?php if (true === $hasRouting) { ?>
        $this->_entityRouteConstruct();
    <?php } ?>
    <?php if ($relationClassNameDetail instanceof ClassNameDetails) { ?>
        $this-><?= $relationClassData['plural'] ?> = new ArrayCollection();
    <?php } ?>
    }

    protected function createTranslation(): TranslationInterface
    {
        return new <?= $classNameDetail->getShortName() ?>Translation();
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(?string $locale = null): TranslationInterface
    {
        return $this->_doGetTranslation($locale);
    }

    public static function getTranslationClass(): string
    {
        return <?= $classNameDetail->getShortName() ?>Translation::class;
    }

    #[Serializer\Expose]
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('name')]
    #[Serializer\Type('string')]
    #[Serializer\Groups(['Detailed', 'Default', 'Autocomplete'])]
    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function setName(?string $name): void
    {
        $this->getTranslation()->setName($name);
    }

    public function getSeoTranslations(): Collection
    {
        return $this->translations;
    }

    public function getSlugTranslations(): Collection
    {
        return $this->translations;
    }

    <?php if ($relationClassNameDetail instanceof ClassNameDetails) { ?>
    public function get<?= ucfirst($relationClassData['plural']) ?>(): ?Collection
    {
        return $this-><?= $relationClassData['plural'] ?>;
    }

    public function add<?= $relationClassNameDetail->getShortName() ?>(?<?= $relationClassNameDetail->getShortName() ?> $<?= $relationClassData['singular'] ?>): void
    {
        $this-><?= $relationClassData['plural'] ?>->add($<?= $relationClassData['singular'] ?>);
    <?php if (true === $isOwningSide) { ?>
        if (!$<?= $relationClassData['singular'] ?>->get<?= ucfirst($mainClassData['plural']) ?>()->contains($this)) {
            $<?= $relationClassData['singular'] ?>->add<?= ucfirst($mainClassData['singular']) ?>($this);
        }
    <?php } ?>
    }

    public function remove<?= $relationClassNameDetail->getShortName() ?>(?<?= $relationClassNameDetail->getShortName() ?> $<?= $relationClassData['singular'] ?>): void
    {
        $this-><?= $relationClassData['plural'] ?>->removeElement($<?= $relationClassData['singular'] ?>);
    <?php if (true === $isOwningSide) { ?>
        $<?= $relationClassData['singular'] ?>->add<?= ucfirst($mainClassData['singular']) ?>(null);
    <?php } ?>
    }
    <?php } ?>

    public function getCss(): ?string
    {
        return $this->css;
    }

    public function setCss(string $css): void
    {
        $this->css = $css;
    }

    public function getJs(): ?string
    {
        return $this->js;
    }

    public function setJs(string $js): void
    {
        $this->js = $js;
    }

<?php if (true === $hasRouting) { ?>
    public function getRouteUnikName(): string
    {
        return 'happy_cms_<?= mb_strtolower($scope) ?>_' . $this->getId();
    }

    public function getRouteStaticPrefix(TranslationInterface $translation, bool $isPreview): string
    {
        $slug = (method_exists($translation, 'getSlug') ? $translation->getSlug() : '');
        $locale = $translation->getLocale();
        return sprintf('/%s/<?= mb_strtolower($scope) ?>/%s%s', $locale, $slug, ($isPreview ? '-preview': ''));
    }
<?php } ?>

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
<?php } ?>
