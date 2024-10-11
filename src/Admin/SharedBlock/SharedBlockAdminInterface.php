<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Admin\SharedBlock;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

interface SharedBlockAdminInterface extends ServiceSubscriberInterface, AdminInterface
{
}
