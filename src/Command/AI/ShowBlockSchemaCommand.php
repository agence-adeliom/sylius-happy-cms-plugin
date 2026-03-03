<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command\AI;

use Adeliom\SyliusHappyCMSPlugin\Services\AI\BlockSchemaSerializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'happy-cms:ai:show-block-schema',
    description: 'Display the schema of all available CMS blocks in JSON format for AI consumption',
)]
class ShowBlockSchemaCommand extends Command
{
    public function __construct(
        private readonly BlockSchemaSerializer $blockSchemaSerializer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Include all blocks (not just AI-generatable ones)')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays the schema of all available CMS blocks
in JSON format. This is useful for AI agents to understand the available blocks and their fields.

By default, only blocks marked with #[AIGeneratable] attribute are included:
<info>php %command.full_name%</info>

Include all blocks (not just AI-generatable):
<info>php %command.full_name% --all</info>

Output to a file:
<info>php %command.full_name% --output=/path/to/blocks-schema.json</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('CMS Block Schema Generator');

        try {
            $includeAll = (bool) $input->getOption('all');
            $onlyAIGeneratable = !$includeAll;

            $serializedBlocks = $this->blockSchemaSerializer->serializeBlocks($onlyAIGeneratable);

            if ($onlyAIGeneratable) {
                $io->success(sprintf('Found %d AI-generatable blocks', $serializedBlocks['total_count']));
            } else {
                $io->success(sprintf('Found %d blocks (all)', $serializedBlocks['total_count']));
            }

            // Display summary
            $io->section('Blocks Summary');
            $tableRows = [];
            foreach ($serializedBlocks['blocks'] as $block) {
                /** @var array{name: string, namespace: string, fields: array} $block */
                $tableRows[] = [
                    $block['name'],
                    $block['namespace'],
                    count($block['fields']),
                ];
            }
            $io->table(['Name', 'Namespace', 'Fields'], $tableRows);

            // Output JSON
            $json = $this->blockSchemaSerializer->toJson($onlyAIGeneratable);

            $outputFile = $input->getOption('output');
            if (!empty($outputFile) && is_string($outputFile)) {
                file_put_contents($outputFile, $json);
                $io->success(sprintf('Schema written to: %s', $outputFile));
            } else {
                $io->section('JSON Schema');
                $output->writeln($json);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error generating block schema: %s', $e->getMessage()));

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), 'ERROR', 'fg=white;bg=red', ' ', true);
            }

            return Command::FAILURE;
        }
    }
}
