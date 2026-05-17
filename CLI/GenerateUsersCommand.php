<?php

declare(strict_types=1);

namespace openvk\CLI;

use Chandler\Security\User as ChandlerUser;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Exceptions\InvalidUserNameException;
use openvk\Web\Util\Validator;
use Random\RandomException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class GenerateUsersCommand extends Command
{
    protected static $defaultName = "generate-users";
    protected function configure(): void
    {
        $this->setDescription("Generate test user accounts for development")
            ->addOption(
                "count",
                "c",
                InputOption::VALUE_REQUIRED,
                "Number of users to create",
                1
            );
    }

    //Duplicate the logic from the Web/Presenters/AuthPresenter.php class
    /**
     * @throws RandomException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption("count");

        if ($count < 1) {
            $io->error("Count must be at least 1.");

            return Command::FAILURE;
        }

        $created = [];
        $prefixTimeForEmail = dechex(time());

        for ($i = 1; $i <= $count; $i++) {
            $email = "generated.{$prefixTimeForEmail}.{$i}@localhost.localdomain";
            $password = $this->generatePassword();

            try {
                $user = new User();
                $user->setFirst_Name("Test");
                $user->setLast_Name('TEST LAST NAME');
                $user->setSex(0);
                $user->setEmail($email);
                $user->setSince(date("Y-m-d H:i:s"));
                $user->setRegistering_Ip("127.0.0.1");
                $user->setBirthday(strtotime("1999-01-01"));
                $user->setActivated(1);
            } catch (InvalidUserNameException $ex) {
                $io->error("Failed to set name for user #{$i}: " . $ex->getMessage());

                return Command::FAILURE;
            }

            $chUser = ChandlerUser::create($email, $password);
            if (!$chUser) {
                $io->error("Failed to create Chandler user for {$email}");

                return Command::FAILURE;
            }

            $user->setUser($chUser->getId());
            $user->save(false);

            $created[] = [
                "id" => $user->getId(),
                "email" => $email,
                "password" => $password,
                "url" => "/id" . $user->getId(),
            ];
        }

        $io->success("Created " . count($created) . " user(s).");

        $rows = array_map(static fn(array $u): array => [
            $u["id"],
            $u["email"],
            $u["password"],
            $u["url"],
        ], $created);

        $io->table(["ID", "Email", "Password", "Profile URL"], $rows);

        return Command::SUCCESS;
    }

    /**
     * @throws RandomException
     */
    private function generatePassword(): string
    {
        do {
            $password = "OvK" . bin2hex(random_bytes(4)) . "A1";
        } while (!Validator::i()->passwordStrong($password));

        return $password;
    }

}
