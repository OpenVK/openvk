<?php declare(strict_types=1);
namespace openvk\CLI;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Photos;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\ImageException;

class ProcessDDEXCommand extends Command
{
    private $images;

    protected static $defaultName = "process-ddex";

    function __construct()
    {
        $this->images = DatabaseConnection::i()->getContext()->table("photos");

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Process DDEX ERN packages for music uploading")
            ->setHelp("This command allows you to process all DDEX ERN packages for music uploading");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $header  = $output->section();
        $counter = $output->section();

        $header->writeln([
            "DDEX ERN processor utility",
            "=====================",
            "",
        ]);

        return Command::SUCCESS;
    }
}
