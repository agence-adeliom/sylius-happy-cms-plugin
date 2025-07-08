<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command;

use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteRenderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'happycms:cache:invalidate',
    description: '',
)]
class InvalidCacheCommand extends Command
{
    public function __construct(
        protected readonly RouteRenderService $routeRenderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->routeRenderService->invalidCache() ? Command::SUCCESS : Command::FAILURE;
    }
}
