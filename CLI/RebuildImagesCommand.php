<?php

declare(strict_types=1);

namespace openvk\CLI;

use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Photos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\ImageException;

class RebuildImagesCommand extends Command
{
    private $images;

    protected static $defaultName = "build-images";

    public function __construct()
    {
        $ctx = DatabaseConnection::i()->getContext();
        if (in_array("photos", $ctx->getStructure()->getTables())) {
            $this->images = $ctx->table("photos");
        }

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Create resized versions of images")
            ->setHelp("This command allows you to resize all your images after configuration change")
            ->addOption("upgrade-only", "U", InputOption::VALUE_NEGATABLE, "Only upgrade images which aren't resized?");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $header  = $output->section();
        $counter = $output->section();

        $header->writeln([
            "Image Rebuild Utility",
            "=====================",
            "",
        ]);

        $filter = ["deleted" => false];
        if ($input->getOption("upgrade-only")) {
            $filter["sizes"] = null;
        }

        $selection = $this->images->select("id")->where($filter);
        $totalPics = $selection->count();
        $header->writeln([
            "Total of $totalPics images found.",
            "",
        ]);

        $errors  = 0;
        $count   = 0;
        $avgTime = null;
        $begin   = new \DateTimeImmutable("now");
        foreach ($selection as $idHolder) {
            $start = microtime(true);

            try {
                $photo = (new Photos())->get($idHolder->id);
                $photo->getSizes(true, true);
                $photo->getDimensions();
            } catch (ImageException $ex) {
                $errors++;
            }

            $timeConsumed = microtime(true) - $start;
            if (!$avgTime) {
                $avgTime = $timeConsumed;
            } else {
                $avgTime = ($avgTime + $timeConsumed) / 2;
            }

            $eta = $begin->getTimestamp() + ceil($totalPics * $avgTime);
            $int = (new \DateTimeImmutable("now"))->diff(new \DateTimeImmutable("@$eta"));
            $int = $int->d . "d" . $int->h . "h" . $int->i . "m" . $int->s . "s";
            $pct = floor(100 * ($count / $totalPics));

            $counter->overwrite("Processed " . ++$count . " images... ($pct% $int left $errors/$count fail)");
        }

        $counter->overwrite("Processing finished :3");

        return Command::SUCCESS;
    }
}
