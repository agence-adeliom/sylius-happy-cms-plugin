<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Menu;

use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Menu\MenuNotFoundException;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Menu\TemplateNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    /*
    * @param \Doctrine\ORM\EntityRepository $menuClass
    */
    public function __construct(
        private readonly Environment $twig,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'happy_cms_menu',
                (fn (Environment $env, array $context, $code, array $extra = []): Markup => $this->renderMenu($env, $context, $code, $extra))(...),
                ['is_safe' => ['js', 'html'], 'needs_context' => true, 'needs_environment' => true],
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderMenu(Environment $env, array $context, string $code, array $extra = []): Markup
    {
        $repo = $this->em->getRepository(MenuInterface::class);
        if (!method_exists($repo, 'findOneByCode')) {
            throw new MenuNotFoundException($code);
        }

        $menu = $repo->findOneByCode($code);

        if (empty($menu)) {
            throw new MenuNotFoundException($code);
        }

        $template = '@SyliusHappyCMSPlugin/front/menus/' . $code . '.html.twig';

        if (!empty($extra['template'])) {
            $template = $extra['template'];
        }

        if (!$this->twig->getLoader()->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        $rootItem = $this->em->getRepository(MenuItemInterface::class)->findOneBy([
            'menu' => $menu,
            'parent' => null,
        ]);

        $menu->setRootItem($rootItem);

        return new Markup($this->twig->render($template, array_merge($context, [
            'menu' => $menu,
        ], $extra)), 'UTF-8');
    }
}
