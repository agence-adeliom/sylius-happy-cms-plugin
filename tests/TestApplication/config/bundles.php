<?php

return [
    Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
    Adeliom\SyliusHappyCMSPlugin\SyliusHappyCMSPlugin::class => ['all' => true],
    Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle::class => ['all' => true],
    Presta\SitemapBundle\PrestaSitemapBundle::class => ['all' => true],
    EmilePerron\TinymceBundle\TinymceBundle::class => ['all' => true],
    Symfony\AI\AiBundle\AiBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['test' => true, 'dev' => true],
    FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle::class => ['test' => true],
];
