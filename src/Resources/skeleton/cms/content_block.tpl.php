<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($classNameDetail) && $classNameDetail instanceof ClassNameDetails &&
    isset($parentClassNameDetail) && $parentClassNameDetail instanceof ClassNameDetails &&
    isset($scope)
) {
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= Str::getNamespace($classNameDetail->getFullName()) ?>;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlock;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use <?= $parentClassNameDetail->getFullName() ?>Interface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'happy_cms_<?= mb_strtolower($scope) ?>__<?= Str::asSnakeCase($parentClassNameDetail->getShortName()) ?>_content_block')]
#[ORM\Index(columns: ['<?= Str::asSnakeCase($parentClassNameDetail->getShortName()) ?>_id', 'locale', 'position'], name: 'idx_<?= Str::asSnakeCase($parentClassNameDetail->getShortName()) ?>_locale_position')]
#[ORM\Index(columns: ['<?= Str::asSnakeCase($parentClassNameDetail->getShortName()) ?>_id', 'locale', 'published'], name: 'idx_<?= Str::asSnakeCase($parentClassNameDetail->getShortName()) ?>_locale_published')]
class <?= $classNameDetail->getShortName() ?> extends ContentBlock implements ContentBlockInterface
{
    #[ORM\ManyToOne(targetEntity: <?= $parentClassNameDetail->getShortName() ?>Interface::class, inversedBy: 'contentBlocks')]
    #[ORM\JoinColumn(name: '<?= Str::asSnakeCase($parentClassNameDetail->getShortName()) ?>_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    protected ?<?= $parentClassNameDetail->getShortName() ?>Interface $contentOwner = null;

    public function getContentOwner(): ?<?= $parentClassNameDetail->getShortName() ?>Interface
    {
        return $this->contentOwner;
    }

    public function setContentOwner(?<?= $parentClassNameDetail->getShortName() ?>Interface $contentOwner): void
    {
        $this->contentOwner = $contentOwner;
    }

    /**
     * @deprecated Use getContentOwner() instead
     */
    public function get<?= $parentClassNameDetail->getShortName() ?>(): ?<?= $parentClassNameDetail->getShortName() ?>Interface
    {
        return $this->getContentOwner();
    }

    /**
     * @deprecated Use setContentOwner() instead
     */
    public function set<?= $parentClassNameDetail->getShortName() ?>(?<?= $parentClassNameDetail->getShortName() ?>Interface $<?= Str::asLowerCamelCase($parentClassNameDetail->getShortName()) ?>): void
    {
        $this->setContentOwner($<?= Str::asLowerCamelCase($parentClassNameDetail->getShortName()) ?>);
    }
}

<?php } ?>