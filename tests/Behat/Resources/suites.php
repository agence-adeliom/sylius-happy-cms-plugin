<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;

return (new Config())
    ->withProfile(
        (new Profile('default'))
        ->withSuite(
            (new Suite('ui_admin_dashboard'))
            ->withPaths('%paths.base%/features')
            ->withContexts(
                'sylius.behat.context.hook.doctrine_orm',
                'sylius.behat.context.hook.session',
                'sylius.behat.context.transform.channel',
                'sylius.behat.context.transform.lexical',
                'sylius.behat.context.setup.admin_security',
                'sylius.behat.context.setup.channel',
                'sylius.behat.context.ui.admin.dashboard',
                'sylius.behat.context.ui.admin.login',
            )
            ->withFilter(new TagFilter('@admin_dashboard&&@ui&&~@mink:chromedriver')),
        )
        ->withSuite(
            (new Suite('ui_admin_page_builder'))
            ->withPaths('%paths.base%/features')
            ->withContexts(
                'sylius.behat.context.hook.bad_gateway',
                'sylius.behat.context.hook.doctrine_orm',
                'sylius.behat.context.hook.session',
                'sylius.behat.context.transform.channel',
                'sylius.behat.context.transform.lexical',
                'sylius.behat.context.transform.locale',
                'sylius.behat.context.transform.shared_storage',
                'sylius.behat.context.setup.admin_security',
                'sylius.behat.context.setup.channel',
                'sylius.behat.context.setup.locale',
                'sylius.behat.context.ui.admin.login',
                'tests.adeliom_sylius_happy_cms_plugin.behat.context.page_builder',
            )
            ->withFilter(new TagFilter('@admin_page_builder&&@ui&&~@mink:chromedriver')),
        )
        ->withSuite(
            (new Suite('ui_admin_media_csrf'))
            ->withPaths('%paths.base%/features')
            ->withContexts(
                'sylius.behat.context.hook.doctrine_orm',
                'sylius.behat.context.hook.session',
                'sylius.behat.context.transform.channel',
                'sylius.behat.context.transform.lexical',
                'sylius.behat.context.transform.shared_storage',
                'sylius.behat.context.setup.admin_security',
                'sylius.behat.context.setup.channel',
                'sylius.behat.context.ui.admin.login',
                'tests.adeliom_sylius_happy_cms_plugin.behat.context.ui.admin.media_csrf',
            )
            ->withFilter(new TagFilter('@media&&@csrf&&@ui')),
        ),
    )
;
