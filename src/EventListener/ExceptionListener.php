<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener;

use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RedirectionManagerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function __construct(
        protected readonly RedirectionManagerInterface $redirectionManager,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if ($throwable instanceof NotFoundHttpException) {
            if ($response = $this->redirectionManager->onHttpNotFoundException($event)) {
                $event->setResponse($response);
            }
        }
    }
}
