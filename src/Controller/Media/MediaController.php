<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\CsrfProtection;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Delete;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Download;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\GetContent;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\GlobalSearch;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Metas;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Move;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\NewFolder;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Rename;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Upload;
use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\Utils;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaCsrfTokenManager;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaController extends AbstractController
{
    use CsrfProtection;
    use Utils;
    use GetContent;
    use Delete;
    use Download;
    use Move;
    use Rename;
    use Metas;
    use Upload;
    use NewFolder;
    use GlobalSearch;

    protected TranslatorInterface $translator;

    protected EventDispatcherInterface $eventDispatcher;

    protected string $ignoreFiles = '';

    protected string $chunksDir = '';

    protected int $paginationAmount = 50;

    protected FilesystemOperator $filesystem;

    protected ObjectManager $em;

    protected MediaHelper $helper;

    protected MediaManager $manager;

    protected ManagerRegistry $managerRegistry;

    public function __construct(MediaManager $manager, ManagerRegistry $managerRegistry, ParameterBagInterface $bag, EventDispatcherInterface $dispatcher, TranslatorInterface $translator, MediaCsrfTokenManager $mediaCsrfTokenManager)
    {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->mediaCsrfTokenManager = $mediaCsrfTokenManager;
        $this->em = $this->managerRegistry->getManager();

        if (is_string($bag->get('sylius_happy_cms.media.ignore_files'))) {
            $this->ignoreFiles = $bag->get('sylius_happy_cms.media.ignore_files');
        }
        if (is_int($bag->get('sylius_happy_cms.media.pagination_amount'))) {
            $this->paginationAmount = $bag->get('sylius_happy_cms.media.pagination_amount');
        }
        if (is_string($bag->get('kernel.project_dir'))) {
            $this->chunksDir = $bag->get('kernel.project_dir') . '/var/chunks_upload';
        }
        $this->helper = $manager->getHelper();
        $this->filesystem = $manager->getFilesystem();
        $this->eventDispatcher = $dispatcher;
        $this->translator = $translator;
    }

    public function index(Request $request): Response
    {
        return $this->render('@SyliusHappyCMSPlugin/media/manager_view.html.twig', [
            'csrf_token' => $this->getCsrfToken($request),
        ]);
    }

    public function browse(Request $request): Response
    {
        $data = [
            'provider' => $request->query->get('provider'),
            'restrict' => $request->query->all('restrict'),
            'CKEditor' => $request->query->get('CKEditor'),
            'CKEditorFuncNum' => $request->query->get('CKEditorFuncNum'),
            'langCode' => $request->query->get('langCode', 'en'),
            'csrf_token' => $this->getCsrfToken($request),
        ];

        return $this->render('@SyliusHappyCMSPlugin/media/browser.html.twig', $data);
    }
}
