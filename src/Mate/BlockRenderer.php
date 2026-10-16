<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Mate;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Renders the flex blocks of a content one by one for the Mate tools, through the block helper of the application
 * (the one the front uses, possibly decorated), and reports the outcome of each block.
 *
 * A decorating helper may log and skip a failing block: when the helper returns nothing for a published, registered
 * block, the block template is rendered again directly, with the variables Helper::renderBlock() passes, to surface
 * the exception the helper swallowed.
 */
final class BlockRenderer
{
    private bool $pushed = false;

    public function __construct(
        private readonly Helper $helper,
        private readonly Environment $twig,
        private readonly ?RequestStack $requestStack,
        Request $request,
    ) {
        if (null !== $this->requestStack && null === $this->requestStack->getCurrentRequest()) {
            $this->requestStack->push($request);
            $this->pushed = true;
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function render(array $data, ?BlockTypeInterface $block): array
    {
        // The helper decides first: a decorating helper may resolve a legacy block type or publish a block without
        // the "block_published" key.
        try {
            $html = $this->helper->renderBlock($data);
        } catch (\Throwable $exception) {
            return ['status' => 'error'] + $this->describeException($exception);
        }

        if (null !== $html) {
            $length = \strlen(trim((string) $html));

            return ['status' => 0 === $length ? 'empty' : 'rendered', 'html_length' => $length];
        }

        if (null === $block) {
            return ['status' => 'unknown_type'];
        }

        if (!($data['block_published'] ?? false)) {
            return ['status' => 'unpublished'];
        }

        try {
            $this->twig->render($block->getFrontEndTemplatePath(), [
                'block' => $data,
                'preview' => false,
                'blockType' => $data['block_type'] ?? null,
                'settings' => array_diff_key($data, ['block_type' => true, 'position' => true]),
            ]);
        } catch (\Throwable $exception) {
            return ['status' => 'error', 'swallowed_by' => $this->helper::class] + $this->describeException($exception);
        }

        return ['status' => 'skipped', 'note' => sprintf('%s returned nothing although the template renders on its own: a "happy_cms_block.render_block" listener or the helper skipped it.', $this->helper::class)];
    }

    public function close(): void
    {
        if ($this->pushed) {
            $this->requestStack?->pop();
            $this->pushed = false;
        }
    }

    /**
     * @return array{exception: string, message: string, at: string}
     */
    private function describeException(\Throwable $exception): array
    {
        // Twig wraps the exceptions thrown by the template code: the previous one is the cause.
        $cause = $exception;
        while ($cause instanceof \Twig\Error\RuntimeError && null !== $cause->getPrevious()) {
            $cause = $cause->getPrevious();
        }

        return [
            'exception' => $cause::class,
            'message' => $exception->getMessage(),
            'at' => sprintf('%s:%d', $cause->getFile(), $cause->getLine()),
        ];
    }
}
