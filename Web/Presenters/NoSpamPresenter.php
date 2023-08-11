<?php declare(strict_types=1);

namespace openvk\Web\Presenters;

use Nette\Database\DriverException;
use Nette\Utils\Finder;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Entities\Comment;
use Chandler\Database\Log;
use openvk\Web\Models\Entities\NoSpamLog;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\ChandlerUsers;
use Chandler\Database\Logs;
use openvk\Web\Models\Repositories\NoSpamLogs;
use openvk\Web\Models\Repositories\Users;

final class NoSpamPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    protected $deactivationTolerant = true;
    protected $presenterName = "nospam";

    const ENTITIES_NAMESPACE = "openvk\\Web\\Models\\Entities";

    function __construct()
    {
        parent::__construct();
    }

    function renderIndex(): void
    {
        $this->assertUserLoggedIn();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $targetDir = __DIR__ . '/../Models/Entities/';
        $mode = in_array($this->queryParam("act"), ["form", "templates", "rollback", "reports"]) ? $this->queryParam("act") : "form";

        if ($mode === "form") {
            $this->template->_template = "NoSpam/Index";
            $foundClasses = [];
            foreach (Finder::findFiles('*.php')->from($targetDir) as $file) {
                $content = file_get_contents($file->getPathname());
                $namespacePattern = '/namespace\s+([^\s;]+)/';
                $classPattern = '/class\s+([^\s{]+)/';
                preg_match($namespacePattern, $content, $namespaceMatches);
                preg_match($classPattern, $content, $classMatches);

                if (isset($namespaceMatches[1]) && isset($classMatches[1])) {
                    $classNamespace = trim($namespaceMatches[1]);
                    $className = trim($classMatches[1]);
                    $fullClassName = $classNamespace . '\\' . $className;

                    if ($classNamespace === NoSpamPresenter::ENTITIES_NAMESPACE && class_exists($fullClassName)) {
                        $foundClasses[] = $className;
                    }
                }
            }

            $models = [];

            foreach ($foundClasses as $class) {
                $r = new \ReflectionClass(NoSpamPresenter::ENTITIES_NAMESPACE . "\\$class");
                if (!$r->isAbstract() && $r->getName() !== NoSpamPresenter::ENTITIES_NAMESPACE . "\\Correspondence")
                    $models[] = $class;
            }
            $this->template->models = $models;
        } else if ($mode === "templates") {
            $this->template->_template = "NoSpam/Templates.xml";
            $filter = [];
            if ($this->queryParam("id")) {
                $filter["id"] = (int)$this->queryParam("id");
            }
            $this->template->templates = iterator_to_array((new NoSpamLogs)->getList($filter));
        } else if ($mode === "reports") {
            $this->redirect("/scumfeed");
        } else {
            $template = (new NoSpamLogs)->get((int)$this->postParam("id"));
            if (!$template || $template->isRollbacked())
                $this->returnJson(["success" => false, "error" => "Шаблон не найден"]);

            $model = NoSpamPresenter::ENTITIES_NAMESPACE . "\\" . $template->getModel();
            $items = $template->getItems();
            if (count($items) > 0) {
                $db = DatabaseConnection::i()->getContext();

                $unbanned_ids = [];
                foreach ($items as $_item) {
                    try {
                        $item = new $model;
                        $table_name = $item->getTableName();
                        $item = $db->table($table_name)->get((int)$_item);
                        if (!$item) continue;

                        $item = new $model($item);

                        if (key_exists("deleted", $item->unwrap()) && $item->isDeleted()) {
                            $item->setDeleted(0);
                            $item->save();
                        }

                        if (in_array($template->getTypeRaw(), [2, 3])) {
                            $owner = NULL;
                            $methods = ["getOwner", "getUser", "getRecipient", "getInitiator"];

                            if (method_exists($item, "ban")) {
                                $owner = $item;
                            } else {
                                foreach ($methods as $method) {
                                    if (method_exists($item, $method)) {
                                        $owner = $item->$method();
                                        break;
                                    }
                                }
                            }

                            $_id = ($owner instanceof Club ? $owner->getId() * -1 : $owner->getId());

                            if (!in_array($_id, $unbanned_ids)) {
                                $owner->unban($this->user->id);
                                $unbanned_ids[] = $_id;
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->returnJson(["success" => false, "error" => $e->getMessage()]);
                    }
                }
            } else {
                $this->returnJson(["success" => false, "error" => "Объекты не найдены"]);
            }

            $template->setRollback(true);
            $template->save();

            $this->returnJson(["success" => true]);
        }
    }

    function renderSearch(): void
    {
        $this->assertUserLoggedIn();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        $this->assertNoCSRF();
        $this->willExecuteWriteAction();

        function searchByAdditionalParams(?string $table = NULL, ?string $where = NULL, ?string $ip = NULL, ?string $useragent = NULL, ?int $ts = NULL, ?int $te = NULL, $user = NULL)
        {
            $db = DatabaseConnection::i()->getContext();
            if ($table && ($ip || $useragent || $ts || $te || $user)) {
                $conditions = [];

                if ($ip) $conditions[] = "`ip` REGEXP '$ip'";
                if ($useragent) $conditions[] = "`useragent` REGEXP '$useragent'";
                if ($ts) $conditions[] = "`ts` < $ts";
                if ($te) $conditions[] = "`ts` > $te";
                if ($user) {
                    $users = new Users;

                    $_user = $users->getByChandlerUser((new ChandlerUsers)->getById($user))
                        ?? $users->get((int)$user)
                        ?? $users->getByAddress($user)
                        ?? NULL;

                    if ($_user) {
                        $conditions[] = "`user` = '" . $_user->getChandlerGUID() . "'";
                    }
                }

                $whereStart = "WHERE `object_table` = '$table'";
                if ($table === "profiles") {
                    $whereStart .= "AND `type` = 0";
                }

                $conditions = count($conditions) > 0 ? "AND (" . implode(" AND ", $conditions) . ")" : "";
                $response = [];

                if ($conditions) {
                    $logs = $db->query("SELECT * FROM `ChandlerLogs` $whereStart $conditions GROUP BY `object_id`, `object_model`");

                    if (!$where) {
                        foreach ($logs as $log) {
                            $log = (new Logs)->get($log->id);
                            $response[] = $log->getObject()->unwrap();
                        }
                    } else {
                        foreach ($logs as $log) {
                            $log = (new Logs)->get($log->id);
                            $object = $log->getObject()->unwrap();

                            if (!$object) continue;
                            if (str_starts_with($where, " AND")) {
                                $where = substr_replace($where, "", 0, strlen(" AND"));
                            }

                            foreach ($db->query("SELECT * FROM `$table` WHERE $where")->fetchAll() as $o) {
                                if ($object->id === $o["id"]) {
                                    $response[] = $object;
                                }
                            }
                        }
                    }
                }

                return $response;
            }
        }

        try {
        $response = [];
        $processed = 0;

        $where = $this->postParam("where");
        $ip = $this->postParam("ip");
        $useragent = $this->postParam("useragent");
        $searchTerm = $this->postParam("q");
        $ts = (int)$this->postParam("ts");
        $te = (int)$this->postParam("te");
        $user = $this->postParam("user");

        if (!$ip && !$useragent && !$searchTerm && !$ts && !$te && !$where && !$searchTerm && !$user)
            $this->returnJson(["success" => false, "error" => "Нет запроса. Заполните поле \"подстрока\" или введите запрос \"WHERE\" в поле под ним."]);

        $models = explode(",", $this->postParam("models"));

        foreach ($models as $_model) {
            $model_name = NoSpamPresenter::ENTITIES_NAMESPACE . "\\" . $_model;
            if (!class_exists($model_name)) {
                continue;
            }

            $model = new $model_name;

            $c = new \ReflectionClass($model_name);
            if ($c->isAbstract() || $c->getName() == NoSpamPresenter::ENTITIES_NAMESPACE . "\\Correspondence") {
                continue;
            }

            $db = DatabaseConnection::i()->getContext();
            $table = $model->getTableName();
            $columns = $db->getStructure()->getColumns($table);

            if ($searchTerm) {
                $conditions = [];
                $need_deleted = false;
                foreach ($columns as $column) {
                    if ($column["name"] == "deleted") {
                        $need_deleted = true;
                    } else {
                        $conditions[] = "`$column[name]` REGEXP '$searchTerm'";
                    }
                }
                $conditions = implode(" OR ", $conditions);

                $where = ($this->postParam("where") ? " AND ($conditions)" : "($conditions)");
                if ($need_deleted) $where .= " AND (`deleted` = 0)";
            }

            $rows = [];
            if ($ip || $useragent || $ts || $te || $user) {
                $rows = searchByAdditionalParams($table, $where, $ip, $useragent, $ts, $te, $user);
            }

            if (count($rows) === 0) {
                if (!$searchTerm) {
                    if (str_starts_with($where, " AND")) {
                        if ($searchTerm && !$this->postParam("where")) {
                            $where = substr_replace($where, "", 0, strlen(" AND"));
                        } else {
                            $where = "(" . $this->postParam("where") . ")" . $where;
                        }
                    }

                    if (!$where) {
                        $rows = [];
                    } else {
                        $result = $db->query("SELECT * FROM `$table` WHERE $where");
                        $rows = $result->fetchAll();
                    }
                }
            }

            if (!in_array((int)$this->postParam("ban"), [1, 2, 3])) {
                foreach ($rows as $key => $object) {
                    $object = (array)$object;
                    $_obj = [];
                    foreach ($object as $key => $value) {
                        foreach ($columns as $column) {
                            if ($column["name"] === $key && in_array(strtoupper($column["nativetype"]), ["BLOB", "BINARY", "VARBINARY", "TINYBLOB", "MEDIUMBLOB", "LONGBLOB"])) {
                                $value = "[BINARY]";
                                break;
                            }
                        }

                        $_obj[$key] = $value;
                        $_obj["__model_name"] = $_model;
                    }
                    $response[] = $_obj;
                }
            } else {
                $ids = [];

                foreach ($rows as $object) {
                    $object = new $model_name($db->table($table)->get($object->id));
                    if (!$object) continue;
                    $ids[] = $object->getId();
                }

                $log = new NoSpamLog;
                $log->setUser($this->user->id);
                $log->setModel($_model);
                if ($searchTerm) {
                    $log->setRegex($searchTerm);
                } else {
                    $log->setRequest($where);
                }
                $log->setBan_Type((int)$this->postParam("ban"));
                $log->setCount(count($rows));
                $log->setTime(time());
                $log->setItems(implode(",", $ids));
                $log->save();

                $banned_ids = [];
                foreach ($rows as $object) {
                    $object = new $model_name($db->table($table)->get($object->id));
                    if (!$object) continue;

                    $owner = NULL;
                    $methods = ["getOwner", "getUser", "getRecipient", "getInitiator"];

                    if (method_exists($object, "ban")) {
                        $owner = $object;
                    } else {
                        foreach ($methods as $method) {
                            if (method_exists($object, $method)) {
                                $owner = $object->$method();
                                break;
                            }
                        }
                    }

                    if ($owner instanceof User && $owner->getId() === $this->user->id) {
                        if (count($rows) === 1) {
                            $this->returnJson(["success" => false, "error" => "\"Производственная травма\" — Вы не можете блокировать или удалять свой же контент"]);
                        } else {
                            continue;
                        }
                    }

                    if (in_array((int)$this->postParam("ban"), [2, 3])) {
                        if ($owner) {
                            $_id = ($owner instanceof Club ? $owner->getId() * -1 : $owner->getId());
                            if (!in_array($_id, $banned_ids)) {
                                if ($owner instanceof User) {
                                    $owner->ban("**content-noSpamTemplate-" . $log->getId() . "**", false, time() + $owner->getNewBanTime(), $this->user->id);
                                } else {
                                    $owner->ban("Подозрительная активность");
                                }

                                $banned_ids[] = $_id;
                            }
                        }
                    }

                    if (in_array((int)$this->postParam("ban"), [1, 3]))
                        $object->delete();
                }

                $processed++;
            }
        }

        $this->returnJson(["success" => true, "processed" => $processed, "count" => count($response), "list" => $response]);
        } catch (\Throwable $e) {
            $this->returnJson(["success" => false, "error" => $e->getMessage()]);
        }
    }
}
