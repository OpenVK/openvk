<?php

declare(strict_types=1);

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
    private $transactions;

    protected static $defaultName = "fetch-ton";

    public function __construct()
    {
        $ctx = DatabaseConnection::i()->getContext();
        if (in_array("cryptotransactions", $ctx->getStructure()->getTables())) {
            $this->transactions = $ctx->table("cryptotransactions");
        }

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription("Fetches TON transactions to top up the users' balance")
             ->setHelp("This command checks for new transactions on TON Wallet and then top up the balance of specified users");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $header = $output->section();

        $header->writeln([
            "TONCOIN Fetcher",
            "=====================",
            "",
        ]);

        if (!OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["enabled"]) {
            $header->writeln("Sorry, but you handn't enabled the TON support in your config file yet.");

            return Command::FAILURE;
        }

        $testnetSubdomain = OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["testnet"] ? "testnet." : "";
        $url              = "https://" . $testnetSubdomain . "toncenter.com/api/v2/getTransactions?";

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Accept: application/json",
            ],
        ];

        $selection = $this->transactions->select('hash, lt')->order("id DESC")->limit(1)->fetch();
        $trHash    = $selection->hash ?? null;
        $trLt      = $selection->lt ?? null;

        $data = http_build_query([
            "address" => OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["address"],
            "limit"   => 100,
            "hash"    => $trHash,
            "to_lt"   => $trLt,
        ]);

        $response = file_get_contents($url . $data, false, stream_context_create($opts));
        $response = json_decode($response, true);

        $header->writeln("Gonna up the balance of users");
        foreach ($response["result"] as $transfer) {
            preg_match('/' . OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["regex"] . '/', $transfer["in_msg"]["message"], $outputArray);
            $userId = ctype_digit($outputArray[1]) ? intval($outputArray[1]) : null;
            if (is_null($userId)) {
                $header->writeln("Well, that's a donation. Thanks! XD");
            } else {
                $user = (new Users())->get($userId);
                if (!$user) {
                    $header->writeln("Well, that's a donation. Thanks! XD");
                } else {
                    $value = ($transfer["in_msg"]["value"] / NANOTON) / OPENVK_ROOT_CONF["openvk"]["preferences"]["ton"]["rate"];
                    $user->setCoins($user->getCoins() + $value);
                    $user->save();
                    (new CoinsTransferNotification($user, (new Users())->get(OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["adminAccount"]), (int) $value, "Via TON cryptocurrency"))->emit();
                    $header->writeln($value . " coins are added to " . $user->getId() . " user id");
                    $this->transactions->insert([
                        "id"   => null,
                        "hash" => $transfer["transaction_id"]["hash"],
                        "lt"   => $transfer["transaction_id"]["lt"],
                    ]);
                }
            }
        }

        $header->writeln("Processing finished :3");

        return Command::SUCCESS;
    }
}
