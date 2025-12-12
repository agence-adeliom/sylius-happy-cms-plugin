<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Behat\Context;

use Adeliom\SyliusHappyCMSPlugin\Command\Starter\CreateDemoPagesCommand;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Behat\Behat\Context\Context;
use Behat\Mink\Session;
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

    /**
     * @Given there is a page with template :template
     */
    public function thereIsAPageWithTemplate(string $template): void
    {
        // Check if page already exists
        /** @var PageInterface|null $page */
        $page = $this->entityManager->getRepository(PageInterface::class)->findOneBy([
            'template' => $template,
        ]);

        if (null === $page) {
            // Execute the command to create demo pages
            $input = new ArrayInput([]);
            $output = new BufferedOutput();

            $this->createDemoPagesCommand->run($input, $output);

            // Fetch the page again
            $page = $this->entityManager->getRepository(PageInterface::class)->findOneBy([
                'template' => $template,
            ]);
        }

        $this->lastPage = $page;
    }

    /**
     * @When I go to the page builder
     */
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

    /**
     * @Then I should be on the page builder page
     */
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
