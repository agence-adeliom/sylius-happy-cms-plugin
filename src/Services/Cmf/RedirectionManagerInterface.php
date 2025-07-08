<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Cmf;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

interface RedirectionManagerInterface
{
    public function onHttpNotFoundException(ExceptionEvent $event): ?Response;
}
