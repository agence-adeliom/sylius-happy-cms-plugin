<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($classNameDetail) && $classNameDetail instanceof ClassNameDetails &&
    isset($scope, $hasFlexibleContent, $extraFields)
) {
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= Str::getNamespace($classNameDetail->getFullName()) ?>;

use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsSeoInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\SeoInterface;
use Adeliom\SyliusHappyCMSPlugin\Traits\Seo\EntitySeoTrait;
use Adeliom\SyliusHappyCMSPlugin\Traits\EntityNameSlugTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;
use Sylius\Resource\Model\AbstractTranslation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity]
#[ORM\UniqueConstraint(name: '<?= mb_strtolower($scope) ?>_<?= Str::asSnakeCase($classNameDetail->getShortName()) ?>__slug_locale', columns: ['slug', 'locale'])]
#[ORM\Table(name: 'happy_cms_<?= mb_strtolower($scope) ?>__<?= Str::asSnakeCase($classNameDetail->getShortName()) ?>_translation')]
class <?= $classNameDetail->getShortName() ?> extends AbstractTranslation implements TranslationInterface, ResourceInterface, CmsSeoInterface, \Stringable
{
    use EntityIdTrait;
    use EntityNameSlugTrait;
    use EntitySeoTrait {
        EntitySeoTrait::__construct as private _SEOConstruct;
    }

<?php if ($hasFlexibleContent) { ?>
    /**
    * @var array|null
    */
    #[Groups('main')]
    #[ORM\Column(name: 'content', type: Types::JSON, nullable: true)]
    #[Assert\Type('array')]
    protected $content = [];
<?php }?>

    public function __construct()
    {
        $this->_SEOConstruct();
    }
<?php
if (is_array($extraFields)) {
    foreach ($extraFields as $fieldData) {
        ?>
    #[ORM\Column(type: Types::<?= mb_strtoupper($fieldData['columnType']) ?>)]
    protected ?<?= $fieldData['phpType'] ?> $<?= $fieldData['name'] ?> = null;

    public function get<?= ucfirst($fieldData['name']) ?>(): ?<?= $fieldData['phpType'] ?>
    {
        return $this-><?= $fieldData['name'] ?>;
    }

    public function set<?= ucfirst($fieldData['name']) ?>(?<?= $fieldData['phpType'] ?> $<?= $fieldData['name'] ?>): void
    {
        $this-><?= $fieldData['name'] ?> = $<?= $fieldData['name'] ?>;
    }
<?php
    }
}
    ?>

<?php if ($hasFlexibleContent) { ?>
    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(?array $content): void
    {
        $this->content = $content;
    }

<?php } ?>
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSeoTitle(PrePersistEventArgs|PreUpdateEventArgs $event): void
    {
        if (empty($this->getName())) {
            // $this->setName('No name');
        }
        if (empty($this->getSEO()->title)) {
            $this->getSEO()->title = $this->getName();
        }
    }

    #[ORM\PreRemove]
    public function onRemove(PreRemoveEventArgs $event): void
    {
        $page = $this->getTranslatable();
        $this->setName($this->getName() . '-' . $page->getId() . '-deleted');
        $this->setSlug($this->getSlug() . '-' . $page->getId() . '-deleted');
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}

<?php } ?>
