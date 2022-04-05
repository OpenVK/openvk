<?php declare(strict_types=1);
namespace openvk\CLI;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Photo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildImagesCommand extends Command
{
    private $images;

    protected static $defaultName = "build-images";

    function __construct()
    {
        $this->images = DatabaseConnection::i()->getContext()->table("photos");

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
        if($input->getOption("upgrade-only"))
            $filter["sizes"] = NULL;

        $selection = $this->images->where($filter);
        $header->writeln([
            "Total of " . $selection->count() . " images found.",
            "",
        ]);

        $i = 0;
        foreach($selection as $img) {
            $photo = new Photo($img);
            $photo->getSizes(true, true);
            $photo->getDimensions();

            $counter->overwrite("Processed " . ++$i . " images...");
        }

        $counter->overwrite("Processing finished :3");

        return Command::SUCCESS;
    }
}