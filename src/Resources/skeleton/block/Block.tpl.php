<?php declare(strict_types=1);
echo "<?php\n"; ?>
<?php if (
    isset($namespace, $class_name, $template_name)
) { ?>

namespace <?php echo $namespace; ?>;

<?php if ($use_attribute ?? false) { ?>
use Adeliom\SyliusHappyCMSPlugin\Attribute\AsBlock;
<?php } ?>
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\AbstractBlock;
use Symfony\Component\Form\FormBuilderInterface;

<?php if ($use_attribute ?? false) { ?>
#[AsBlock(name: '<?php echo $class_name; ?>', template: '<?php echo $template_name; ?>')]
<?php } ?>
class <?php echo $class_name; ?> extends AbstractBlock<?php echo "\n"; ?>
{
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
<?php } ?>
}

<?php } ?>
