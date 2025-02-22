<?php

declare(strict_types=1);

namespace openvk\CLI;

use Nette\Database\Connection;
use Chandler\Database\DatabaseConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpgradeCommand extends Command {
    protected static $defaultName = "upgrade";
    
    private Connection $db;
    
    private array $chandlerTables = [
        "CHANDLERACLPERMISSIONALIASES",
        "CHANDLERACLGROUPSPERMISSIONS",
        "CHANDLERACLUSERSPERMISSIONS",
        "CHANDLERACLRELATIONS",
        "CHANDLERGROUPS",
        "CHANDLERTOKENS",
        "CHANDLERUSERS"
    ];
    
    public function __construct()
    {
        $this->db = DatabaseConnection::i()->getConnection();
        
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this->setDescription("Upgrade OpenVK installation")
            ->setHelp("This command upgrades database schema after OpenVK was updated")
            ->addOption("quick", "Q", InputOption::VALUE_NEGATABLE,
                "Don't display warning before migrating database", false)
            ->addOption("repair", "R", InputOption::VALUE_NEGATABLE,
                "Attempt to repair database schema if tables are missing", false)
            ->addOption("oneshot", "O", InputOption::VALUE_NONE,
                "Only execute one operation")
            ->addArgument("chandler", InputArgument::OPTIONAL,
                "Location of Chandler installation");
    }
    
    protected function checkDatabaseReadiness(bool &$chandlerOk, bool &$ovkOk, bool &$migrationsOk): void
    {
        $tables = $this->db->query("SHOW TABLES")->fetchAll();
        $tables = array_map(fn ($x) => strtoupper($x->offsetGet(0)), $tables);
        
        $missingTables = array_diff($this->chandlerTables, $tables);
        if (sizeof($missingTables) == 0)
            $chandlerOk = true;
        else if (sizeof($missingTables) == sizeof($this->chandlerTables))
            $chandlerOk = null;
        else
            $chandlerOk = false;
        
        $ovkOk = in_array("PROFILES", $tables);
        $migrationsOk = in_array("OVK_UPGRADE_HISTORY", $tables);
    }
    
    protected function executeSqlScript(int $errCode, string $script, SymfonyStyle $io, bool $transaction = false): int
    {
        $pdo = $this->db->getPdo();
        
        $res = false;
        try {
            if ($transaction)
                $res = $pdo->beginTransaction();
            
            $res = $pdo->exec($script);
            
            if ($transaction)
                $res = $pdo->commit();
        } catch (\PDOException $e) {}
        
        if ($res === false)
            goto error;
        
        return 0;
        
        error:
        $io->getErrorStyle()->error([
            "Failed to execute SQL statement:",
            implode("\t", $pdo->errorInfo())
        ]);
        
        return $errCode;
    }
    
    protected function getNextLevel(): int
    {
        $record = $this->db->query("SELECT level FROM ovk_upgrade_history ORDER BY level DESC LIMIT 1");
        if (!$record->getRowCount())
            return 0;
        
        return $record->fetchField() + 1;
    }
    
    protected function getMigrationFiles(): array
    {
        $files = [];
        $root = dirname(__DIR__ . "/../install/init-static-db.sql");
        
        foreach (glob("$root/sqls/*.sql") as $file)
            $files[(int) basename($file)] = basename($file);
        
        ksort($files);
        
        return $files;
    }
    
    protected function installChandler(InputInterface $input, SymfonyStyle $io, bool $drop = false): int
    {
        $chandlerLocation = $input->getArgument("chandler") ?? (__DIR__ . "/../../../../");
        $chandlerConfigLocation = "$chandlerLocation/chandler.yml";
        
        if (!file_exists($chandlerConfigLocation)) {
            $err = ["Could not find chandler location. Perhaps your config is too unique?"];
            if (!$input->getOption("chandler"))
                $err[] = "Specify absolute path to your chandler installation using the --chandler option.";
            
            $io->getErrorStyle()->error($err);
            
            return 21;
        }
        
        if ($drop) {
            $bar = new ProgressBar($io, sizeof($this->chandlerTables));
            $io->writeln("Dropping chandler tables...");
            
            foreach ($bar->iterate($this->chandlerTables) as $table)
                $this->db->query("DROP TABLE IF EXISTS $table;");
            
            $io->newLine();
        }
        
        $installFile = file_get_contents("$chandlerLocation/install/init-db.sql");
        
        return $this->executeSqlScript(22, $installFile, $io);
    }
    
    protected function initSchema(SymfonyStyle $io): int
    {
        $installFile = file_get_contents(__DIR__ . "/../install/init-static-db.sql");
        
        return $this->executeSqlScript(31, $installFile, $io);
    }
    
    protected function initUpgradeLog(SymfonyStyle $io): int
    {
        $installFile = file_get_contents(__DIR__ . "/../install/init-migration-table.sql");
        
        return $this->executeSqlScript(31, $installFile, $io);
    }
    
    protected function runMigrations(SymfonyStyle $io, bool $oneshot): int
    {
        $nextLevel = $this->getNextLevel();
        $migrations = array_filter($this->getMigrationFiles(), fn ($id) => $id >= $nextLevel, ARRAY_FILTER_USE_KEY);
        
        if (!sizeof($migrations)) {
            $io->writeln("Database up to date. Nothing left to do.");
            
            return 24;
        }
        
        $uname = addslashes(`whoami`);
        $bar = new ProgressBar($io, sizeof($migrations));
        $bar->setFormat("very_verbose");
        
        foreach ($bar->iterate($migrations) as $num => $migration) {
            $script = file_get_contents(__DIR__ . "/../install/sqls/$migration");
            $res = $this->executeSqlScript(100 + $num, $script, $io, true);
            if ($res != 0) {
                $io->getErrorStyle()->error("Error while executing migration №$num");
                
                return $res;
            }
            
            $t = time();
            $this->db->query("INSERT INTO ovk_upgrade_history VALUES ($num, $t, \"$uname\");");
            
            if ($oneshot)
                return 5;
        }
        
        return 0;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $oneShotMode = $input->getOption("oneshot");
        $io = new SymfonyStyle($input, $output);
        
        if (!$input->getOption("quick")) {
            $io->writeln("Do full backup of the database before executing this command!");
            $io->writeln("Command will resume execution after 5 seconds.");
            $io->writeln("You can skip this warning with --quick option.");
            sleep(5);
        }
        
        $migrationsOk = false;
        $chandlerOk = false;
        $ovkOk = false;
        
        $this->checkDatabaseReadiness($chandlerOk, $ovkOk, $migrationsOk);
        
        $res = -1;
        if ($chandlerOk === null) {
            $io->writeln("Chandler schema not detected, attempting to install...");
            
            $res = $this->installChandler($input, $io);
        } else if ($chandlerOk === false) {
            if ($input->getOption("repair")) {
                $io->warning("Chandler schema detected but is broken, attempting to repair...");
                
                $res = $this->installChandler($input, $io, true);
            } else {
                $io->writeln("Chandler schema detected but is broken");
                $io->writeln("Run command with --repair to repair (PERMISSIONS WILL BE LOST)");
                
                return 1;
            }
        }
        
        if ($res > 0)
            return $res;
        else if ($res == 0 && $oneShotMode)
            return 5;
        
        if (!$ovkOk) {
            $io->writeln("Initializing OpenVK schema...");
            $res = $this->initSchema($io);
            if ($res > 0)
                return $res;
            else if ($oneShotMode)
                return 5;
        }
        
        if (!$migrationsOk) {
            $io->writeln("Initializing upgrade log...");
            $res = $this->initUpgradeLog($io);
            if ($res > 0)
                return $res;
            else if ($oneShotMode)
                return 5;
        }
        
        $io->writeln("Upgrading database...");
        $res = $this->runMigrations($io, $oneShotMode);
        
        if (!$res) {
            $io->success("Database has been upgraded!");
            
            return 0;
        } else if ($res != 24) {
            return $res;
        }
        
        return 0;
    }
}