<?php

declare(strict_types=1);

namespace openvk\CLI;

use openvk\Web\Util\GeoBlock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'geo')]
class GeoCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription("Manage per-entity country restrictions")
            ->addArgument("action", InputArgument::REQUIRED, "block, unblock, list or check")
            ->addArgument("target", InputArgument::OPTIONAL, "OpenVK URL or type:id")
            ->addOption("countries", "c", InputOption::VALUE_REQUIRED, "Comma separated ISO country codes", "RU")
            ->addOption("country", null, InputOption::VALUE_REQUIRED, "Country for list/check")
            ->addOption("reason", "r", InputOption::VALUE_REQUIRED, "Reason")
            ->addOption("ref", null, InputOption::VALUE_REQUIRED, "External demand reference")
            ->addOption("with-attachments", null, InputOption::VALUE_NONE, "Cascade a post block to its attachments");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $geo = GeoBlock::i();
        $action = (string) $input->getArgument("action");
        $targetInput = $input->getArgument("target");

        if ($action === "list") {
            $rows = $geo->getActiveBlocks($input->getOption("country"));
            $table = new Table($output);
            $table->setHeaders(["ID", "Entity", "Country", "Reason", "Ref", "Created at", "Created by"]);
            foreach ($rows as $row) {
                $table->addRow([
                    $row->id,
                    $row->entity_type . ":" . $row->entity_id,
                    $row->country_code,
                    $row->reason,
                    $row->external_ref ?? "-",
                    date("Y-m-d H:i", (int) $row->created_at),
                    $row->created_by,
                ]);
            }

            $table->render();

            return Command::SUCCESS;
        }

        if (!is_string($targetInput) || $targetInput === "") {
            $output->writeln("<error>Target is required</error>");

            return Command::FAILURE;
        }

        $target = $geo->resolveTarget($targetInput);
        if ($target === null) {
            $output->writeln("<error>Could not resolve target: {$targetInput}</error>");

            return Command::FAILURE;
        }

        if ($action === "check") {
            $country = strtoupper((string) ($input->getOption("country") ?: $input->getOption("countries")));
            $blocked = $geo->isBlocked($target["entity"], $country);
            $output->writeln($blocked ? "<comment>blocked</comment>" : "<info>allowed</info>");

            return Command::SUCCESS;
        }

        $reason = trim((string) $input->getOption("reason"));
        if ($reason === "") {
            $output->writeln("<error>--reason is required for block/unblock</error>");

            return Command::FAILURE;
        }

        $countries = array_values(array_unique(array_filter(array_map(
            "strtoupper",
            preg_split("/[\s,]+/", (string) $input->getOption("countries"), -1, PREG_SPLIT_NO_EMPTY) ?: []
        ))));

        if ($action === "block") {
            $inserted = $geo->block(
                $target["type"],
                $target["id"],
                $countries,
                $reason,
                $input->getOption("ref")
            );

            if ($input->getOption("with-attachments") && $target["type"] === "post") {
                foreach ($target["entity"]->getChildren() as $attachment) {
                    $type = $geo->entityType($attachment);
                    if ($type === null) {
                        continue;
                    }

                    foreach ($countries as $country) {
                        $parent = $geo->getActiveBlock($target["type"], $target["id"], $country);
                        $geo->block(
                            $type,
                            $geo->entityId($attachment),
                            [$country],
                            $reason,
                            $input->getOption("ref"),
                            null,
                            $parent?->id
                        );
                    }
                }
            }

            $output->writeln("<info>Blocked {$target['label']} in " . implode(", ", $countries) . "</info>");

            return Command::SUCCESS;
        }

        if ($action === "unblock") {
            $count = $geo->unblock($target["type"], $target["id"], $countries, $reason);
            $output->writeln("<info>Revoked {$count} block(s) for {$target['label']}</info>");

            return Command::SUCCESS;
        }

        $output->writeln("<error>Unknown action: {$action}</error>");

        return Command::FAILURE;
    }
}
