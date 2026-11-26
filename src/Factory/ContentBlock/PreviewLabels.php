<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock;

/**
 * The texts of the page builder preview overlays, already translated. Defaults are in English.
 */
final readonly class PreviewLabels
{
    public function __construct(
        public string $editBlock = 'Edit Block',
        public string $unpublishedBlock = 'Unpublished Block',
        public string $deletedBlock = 'Deleted Block',
        public string $emptyBlock = 'No visible output in the preview',
    ) {
    }
}
