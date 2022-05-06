<?php declare(strict_types=1);
namespace openvk\CLI;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Entities\Notifications\CoinsTransferNotification;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Utils\ImageException;

define("NANOTON", 1000000000); 

class FetchToncoinTransactions extends Command
{
    private $images;

    protected static $defaultName = "fetch-ton";

    function __construct()
    {
        $this->transactions = DatabaseConnection::i()->getContext()->table("cryptotransactions");

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Fetches TON transactions to top up the users' balance")
             ->setHelp("This command checks for new transactions on TON Wallet and then top up the balance of specified users");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $header  = $output->section();
        $counter = $output->section();

        $header->writeln([
            "TONCOIN Fetcher",
            "=====================",
            "",
        ]);

        if(!OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["enabled"])
        {
            $header->writeln([
                "Sorry, but you handn't enabled the TON support in your config file yet.",
                "",
            ]);
            return Command::FAILURE;
        }

        $testnet_subdomain = OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["testnet"] ? "testnet." : "";
        $url = "https://" . $testnet_subdomain . "toncenter.com/api/v2/getTransactions?";

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Accept: application/json"
            ]
        ];

        $selection = $this->transactions->select('hash, lt')->order("id DESC")->limit(1)->fetch();
        $tr_hash = $selection->hash ?? NULL;
        $tr_lt = $selection->lt ?? NULL;

        $data = http_build_query([
            "address" => OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["address"],
            "limit" => 100,
            "hash" => $tr_hash,
            "to_lt" => $tr_lt
        ]);

        $response = file_get_contents($url . $data, false, stream_context_create($opts));
        $response = json_decode($response, true);

        $header->writeln(["Gonna up the balance of users"]);
        foreach($response["result"] as $transfer)
        {
            $output_array;
            preg_match('/ovk=([0-9]+)/', $transfer["in_msg"]["message"], $output_array);
            $userid = ctype_digit($output_array[1]) ? intval($output_array[1]) : null;
            if($userid === null)
            {
                $header->writeln(["Well, that's a donation. Thanks! XD"]);
            }
            else
            {
                $user = (new Users)->get($userid);
                if(!$user) 
                {
                    $header->writeln(["Well, that's a donation. Thanks! XD"]);
                } else {
                    $value = ($transfer["in_msg"]["value"] / NANOTON) / OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["rate"];
                    $user->setCoins($user->getCoins() + $value);
                    $user->save();
                    (new CoinsTransferNotification($user, (new Users)->get(OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["adminAccount"]), 0, "Via TON cryptocurrency"))->emit();
                    $header->writeln([$value . " coins are added to " . $user->getId() . " user id"]);
                    $this->transactions->insert([
                        "id" => null,
                        "hash" => $transfer["transaction_id"]["hash"],
                        "lt" => $transfer["transaction_id"]["lt"]
                    ]);
                }
            }
        }

        $counter->overwrite("Processing finished :3");

        return Command::SUCCESS;
    }
}