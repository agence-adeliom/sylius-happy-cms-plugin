<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\Cmf;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

interface RouteAdminInterface extends ServiceSubscriberInterface, AdminInterface
{
}
