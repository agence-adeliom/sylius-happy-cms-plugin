<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Behat\Context;

use Adeliom\SyliusHappyCMSPlugin\Command\Starter\CreateDemoPagesCommand;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class PageBuilderContext implements Context
{
    private ?PageInterface $lastPage = null;

    public function __construct(
        private readonly Session $session,
        private readonly EntityManagerInterface $entityManager,
        private readonly CreateDemoPagesCommand $createDemoPagesCommand,
        private readonly RouterInterface $router,
    ) {
    }

    #[Given('there is a page with template :template')]
    public function thereIsAPageWithTemplate(string $template): void
    {
        // Check if page already exists
        $page = $this->findPageByTemplate($template);

        if (null === $page) {
            // Execute the command to create demo pages
            $input = new ArrayInput([]);
            $output = new BufferedOutput();

            $exitCode = $this->createDemoPagesCommand->run($input, $output);
            Assert::same($exitCode, 0, sprintf('Demo pages creation failed: %s', $output->fetch()));

            // Fetch the page again
            $page = $this->findPageByTemplate($template);
        }

        $this->lastPage = $page;
    }

    private function findPageByTemplate(string $template): ?PageInterface
    {
        // Homepage is identified by the "homepage" flag since plugin version 2.1
        $criteria = PageInterface::HOMEPAGE === $template ? ['homepage' => true] : ['template' => $template];

        /** @var PageInterface|null $page */
        $page = $this->entityManager->getRepository(PageInterface::class)->findOneBy($criteria);

        return $page;
    }

    #[When('I go to the page builder')]
    public function iGoToThePageBuilder(): void
    {
        $actualId = $this->lastPage?->getId();

        Assert::notNull($actualId, 'No page was created');

        $url = $this->router->generate('sylius_happy_cms_admin_page_builder', [
            'resource' => 'sylius_happy_cms.page',
            'id' => $actualId,
        ]);

        $this->session->visit($url);
    }

    #[Then('I should be on the page builder page')]
    public function iShouldBeOnThePageBuilderPage(): void
    {
        $statusCode = $this->session->getStatusCode();

        Assert::same(
            $statusCode,
            200,
            sprintf('Expected status code 200, but got %d. Current URL: %s', $statusCode, $this->session->getCurrentUrl())
        );
    }
}
