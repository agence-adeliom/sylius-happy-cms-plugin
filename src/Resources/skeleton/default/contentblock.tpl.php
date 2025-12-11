<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($classNameDetail) && $classNameDetail instanceof ClassNameDetails &&
    isset($scope, $parentClassName)
) {
    $parentClassShortName = is_string($parentClassName) ? $parentClassName : $scope;
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= Str::getNamespace($classNameDetail->getFullName()) ?>;

use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlock;
use Adeliom\SyliusHappyCMSPlugin\Entity\ContentBlock\ContentBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\<?= $scope ?>\<?= $parentClassShortName ?>Interface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__<?= Str::asSnakeCase($parentClassShortName) ?>_content_block')]
#[ORM\Index(columns: ['<?= Str::asSnakeCase($parentClassShortName) ?>_id', 'locale', 'position'], name: 'idx_<?= Str::asSnakeCase($parentClassShortName) ?>_locale_position')]
#[ORM\Index(columns: ['<?= Str::asSnakeCase($parentClassShortName) ?>_id', 'locale', 'published'], name: 'idx_<?= Str::asSnakeCase($parentClassShortName) ?>_locale_published')]
class <?= $classNameDetail->getShortName() ?> extends ContentBlock implements ContentBlockInterface
{
    #[ORM\ManyToOne(targetEntity: <?= $parentClassShortName ?>Interface::class)]
    #[ORM\JoinColumn(name: '<?= Str::asSnakeCase($parentClassShortName) ?>_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    protected ?<?= $parentClassShortName ?>Interface $<?= Str::asLowerCamelCase($parentClassShortName) ?> = null;

    public function get<?= $parentClassShortName ?>(): ?<?= $parentClassShortName ?>Interface
    {
        return $this-><?= Str::asLowerCamelCase($parentClassShortName) ?>;
    }

    public function set<?= $parentClassShortName ?>(?<?= $parentClassShortName ?>Interface $<?= Str::asLowerCamelCase($parentClassShortName) ?>): void
    {
        $this-><?= Str::asLowerCamelCase($parentClassShortName) ?> = $<?= Str::asLowerCamelCase($parentClassShortName) ?>;
    }
}

<?php } ?>