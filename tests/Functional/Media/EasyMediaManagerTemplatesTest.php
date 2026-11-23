<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Media;

use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

/**
 * The media manager page and the media field render easy-media-manager with media.easy_media_manager.enabled, and
 * exactly what they rendered in 2.3.4 (Vue application) without it. The fixtures are the templates of 2.3.4.
 */
final class EasyMediaManagerTemplatesTest extends KernelTestCase
{
    private const FIELD_CONTROLLER = 'agence-adeliom--sylius-happy-cms-plugin--media-field';

    public function testTheMediaFieldIsUnchangedWhenTheOptionIsOff(): void
    {
        $twig = $this->twig();
        $options = ['restrictions_uploadTypes' => ['image/*'], 'restrictions_uploadSize' => 2.0, 'delete' => false];

        $html = $this->renderField($twig, '@SyliusHappyCMSPlugin/media/field/media/form.html.twig', $options);

        self::assertSame($this->renderField($twig, '@fixtures/media_form_2.3.4.html.twig', $options), $html);
        self::assertStringContainsString('<happy-cms-media-modal', $html);
    }

    public function testTheMediaFieldRendersTheEasyMediaWidgetWhenTheOptionIsOn(): void
    {
        $twig = $this->twig(enabled: true);

        $html = $this->renderField($twig, '@SyliusHappyCMSPlugin/media/field/media/form.html.twig', [
            'restrictions_uploadTypes' => ['image/*', 'pdf'],
            'restrictions_uploadSize' => 2.5,
            'restrictions_path' => '/products/doors',
            'editor' => false,
            'delete' => false,
        ]);

        self::assertStringNotContainsString('happy-cms-media-modal', $html);
        self::assertStringNotContainsString('initVueMediaWidget', $html);
        self::assertStringContainsString('class="easy-media-field mb-3" data-controller="' . self::FIELD_CONTROLLER . '"', $html);
        self::assertStringContainsString('data-' . self::FIELD_CONTROLLER . '-upload-types-value="[&quot;image\/*&quot;,&quot;pdf&quot;]"', $html);
        self::assertStringContainsString('data-' . self::FIELD_CONTROLLER . '-upload-size-value="2.5"', $html);
        self::assertStringContainsString('data-' . self::FIELD_CONTROLLER . '-path-value="/products/doors"', $html);
        self::assertStringContainsString('&quot;enableEditor&quot;:false', $html);
        self::assertStringContainsString('&quot;enableDelete&quot;:false', $html);
        self::assertStringContainsString('&quot;enableUpload&quot;:true', $html);
        self::assertMatchesRegularExpression('#<input type="hidden" id="image" name="image" required="required" data-' . self::FIELD_CONTROLLER . '-target="input"#', $html);
        self::assertStringContainsString('data-action="' . self::FIELD_CONTROLLER . '#pick"', $html);
        self::assertStringContainsString('class="easy-media-field__preview" data-' . self::FIELD_CONTROLLER . '-target="preview"', $html);
    }

    public function testTheMediaManagerPageIsUnchangedWhenTheOptionIsOff(): void
    {
        $twig = $this->twig();

        $html = $this->renderPage($twig, '@SyliusHappyCMSPlugin/media/manager_view.html.twig');

        self::assertSame($this->renderPage($twig, '@fixtures/manager_view_2.3.4.html.twig'), $html);
        self::assertStringContainsString('[vue media manager]', $html);
        self::assertStringContainsString('/bundles/syliushappycmsplugin/media-form.', $html);
    }

    public function testTheMediaManagerPageMountsEasyMediaWithoutTheVueAssetsWhenTheOptionIsOn(): void
    {
        $twig = $this->twig(enabled: true);

        $html = $this->renderPage($twig, '@SyliusHappyCMSPlugin/media/manager_view.html.twig');

        self::assertStringContainsString('[sidebar]', $html);
        self::assertStringContainsString('[navbar]', $html);
        self::assertStringContainsString('<div class="happy-cms-easy-media-page" data-controller="agence-adeliom--sylius-happy-cms-plugin--media-manager"></div>', $html);
        self::assertStringNotContainsString('[vue media manager]', $html);
        self::assertStringNotContainsString('media-form', $html);
        self::assertStringContainsString('[parent stylesheets]', $html);
        self::assertStringContainsString('[parent javascripts]', $html);
    }

    public function testTheConfigurationHoldsTheRoutesTheOptionsAndTheTheme(): void
    {
        $twig = $this->twig(enabled: true, easyMediaManager: [
            'theme' => ['accent' => 'var(--tblr-primary)', 'tokens' => ['accent-700' => '#0b5e4e']],
            'features' => ['enableDelete' => false],
        ]);
        $request = Request::create('/admin/');
        $request->setLocale('fr_FR');
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $html = $twig->render('@SyliusHappyCMSPlugin/media/easy_media/config.html.twig');

        self::assertMatchesRegularExpression('#^<script type="application/json" id="happy-cms-easy-media-config">(.+)</script>$#s', trim($html));
        preg_match('#<script[^>]*>(.+)</script>#s', $html, $matches);
        /** @var array{locale: string, csrfToken: string, config: array<string, mixed>, features: array<string, bool>, routes: array<string, string>} $settings */
        $settings = json_decode($matches[1], true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('fr_FR', $settings['locale']);
        self::assertSame('', $settings['csrfToken'], 'No session in this request.');
        self::assertSame(['accent' => 'var(--tblr-primary)', 'tokens' => ['accent-700' => '#0b5e4e']], $settings['config']['theme']);
        self::assertSame([], $settings['config']['hideFilesExt']);
        self::assertFalse($settings['config']['generatingAlts']);
        self::assertFalse($settings['features']['enableDelete']);
        self::assertTrue($settings['features']['enableUpload']);
        self::assertSame([
            'files', 'fileInfos', 'upload', 'uploadCropped', 'uploadLink', 'newFolder', 'deleteFile', 'moveFile',
            'renameFile', 'editMetas', 'generateAlt', 'generateAltGroup', 'generateAllAlt', 'globalSearch',
            'folderDownload', 'filesDownload',
        ], array_keys($settings['routes']));
        self::assertStringEndsWith('/get-files', $settings['routes']['files']);
    }

    public function testTheConfigurationHasNoThemeByDefault(): void
    {
        $twig = $this->twig(enabled: true);

        $html = $twig->render('@SyliusHappyCMSPlugin/media/easy_media/config.html.twig');

        self::assertStringNotContainsString('theme', $html);
    }

    /**
     * Twig of the test application, with the 2.3.4 fixtures, a stub admin layout and the given easy-media-manager
     * options in the `sylius_happy_cms.media` global.
     *
     * @param array<string, mixed> $easyMediaManager
     */
    private function twig(bool $enabled = false, array $easyMediaManager = []): Environment
    {
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $fixtures = new FilesystemLoader();
        $fixtures->addPath(__DIR__ . '/Fixtures', 'fixtures');
        $stubs = new ArrayLoader([
            '@SyliusAdmin/shared/layout/base.html.twig' => '{% block metatags %}[parent metatags]{% endblock %}|{% block stylesheets %}[parent stylesheets]{% endblock %}|{% block body %}{% endblock %}|{% block javascripts %}[parent javascripts]{% endblock %}',
            '@SyliusAdmin/shared/crud/common/sidebar.html.twig' => '[sidebar]',
            '@SyliusAdmin/shared/crud/common/navbar.html.twig' => '[navbar]',
            '@SyliusHappyCMSPlugin/media/manager.html.twig' => '[vue media manager]',
        ]);
        $twig->setLoader(new ChainLoader([$stubs, $fixtures, $twig->getLoader()]));

        $globals = $twig->getGlobals();
        /** @var array{media: array<string, mixed>} $happyCms */
        $happyCms = $globals['sylius_happy_cms'];
        /** @var array<string, mixed> $current */
        $current = $happyCms['media']['easy_media_manager'];
        $happyCms['media']['easy_media_manager'] = array_merge($current, $easyMediaManager, ['enabled' => $enabled]);
        $twig->addGlobal('sylius_happy_cms', $happyCms);

        return $twig;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderField(Environment $twig, string $theme, array $options): string
    {
        /** @var FormFactoryInterface $factory */
        $factory = self::getContainer()->get('form.factory');
        $form = $factory->createNamed('image', MediaType::class, null, $options + ['csrf_protection' => false]);

        return $twig->createTemplate(sprintf("{%% form_theme form '%s' %%}{{ form_widget(form) }}", $theme))
            ->render(['form' => $form->createView()]);
    }

    private function renderPage(Environment $twig, string $template): string
    {
        return $twig->render($template, ['csrf_token' => 'token']);
    }
}
