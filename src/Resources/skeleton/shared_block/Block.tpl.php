<?php declare(strict_types=1);
echo "<?php\n"; ?>
<?php if (
    isset($namespace, $class_name, $template_name)
) { ?>

namespace <?php echo $namespace; ?>;

<?php if ($use_attribute ?? false) { ?>
use Adeliom\SyliusHappyCMSPlugin\Attribute\AsSharedBlock;
<?php } ?>
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\AbstractSharedBlockType;
<?php if ($use_attribute ?? false) { ?>
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockAttributeMetadataTrait;
<?php } ?>
use Symfony\Component\Form\FormBuilderInterface;

<?php if ($use_attribute ?? false) { ?>
#[AsSharedBlock(name: '<?php echo $class_name; ?>', template: '<?php echo $template_name; ?>')]
<?php } ?>
class <?php echo $class_name; ?> extends AbstractSharedBlockType<?php echo "\n"; ?>
{
<?php if ($use_attribute ?? false) { ?>
    use SharedBlockAttributeMetadataTrait;

<?php } ?>
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        // Implement with your fields
    }
<?php if (!($use_attribute ?? false)) { ?>

    public function getName(): string
    {
        return '<?php echo $class_name; ?>';
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getFrontEndTemplatePath(): string
    {
        return "<?php echo $template_name; ?>";
    }

    public function getDescription(): string
    {
        return '';
    }
<?php } ?>
}
<?php } ?>
