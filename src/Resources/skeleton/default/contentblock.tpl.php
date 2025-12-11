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

use Adeliom\SyliusHappyCMSPlugin\Entity\<?= $scope ?>\<?= $parentClassShortName ?>ContentBlock as Base<?= $parentClassShortName ?>ContentBlock;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_happy_cms__<?= Str::asSnakeCase($parentClassShortName) ?>_content_block')]
#[ORM\Index(columns: ['<?= Str::asSnakeCase($parentClassShortName) ?>_id', 'locale', 'position'], name: 'idx_<?= Str::asSnakeCase($parentClassShortName) ?>_locale_position')]
#[ORM\Index(columns: ['<?= Str::asSnakeCase($parentClassShortName) ?>_id', 'locale', 'publishState'], name: 'idx_<?= Str::asSnakeCase($parentClassShortName) ?>_locale_published')]
class <?= $classNameDetail->getShortName() ?> extends Base<?= $parentClassShortName ?>ContentBlock
{
}

<?php } ?>
