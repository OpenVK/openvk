<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{GeodbCity, GeodbCountry, GeodbEditor, GeodbFaculty, GeodbLog, GeodbSchool, GeodbSpecialization, GeodbUniversity};
use openvk\Web\Models\Repositories\{GeodbCities, GeodbCountries, GeodbEducation, GeodbFaculties, GeodbLogs, GeodbRights, GeodbSpecializations, Users};
use Chandler\Database\DatabaseConnection;

final class GeodbPresenter extends OpenVKPresenter
{
    private $context;
    private $editors;
    private $countries;
    private $cities;
    private $logs;
    private $education;
    private $faculties;
    private $specializations;
    protected $presenterName = "geodb";

    function __construct
    (
        GeodbRights          $geodbRights,
        GeodbCountries       $geodbCountries,
        GeodbCities          $geodbCities,
        GeodbLogs            $geodbLogs,
        GeodbEducation       $geodbEducation,
        GeodbFaculties       $geodbFaculties,
        GeodbSpecializations $geodbSpecializations
    )
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->editors = $geodbRights;
        $this->countries = $geodbCountries;
        $this->cities = $geodbCities;
        $this->logs = $geodbLogs;
        $this->education = $geodbEducation;
        $this->faculties = $geodbFaculties;
        $this->specializations = $geodbSpecializations;
    }

    function renderIndex(): void
    {
        if (!$this->user->identity->canEditGeodb())
            $this->notFound();

        $mode = in_array($this->queryParam("act"), [
            "countries", "editors", "requests", "add_country", "editor", "country", "city", "add_city", "add_edu",
            "school", "university", "add_faculty", "faculty", "specializations", "specialization", "add_specialization", "logs"
        ]) ? $this->queryParam("act") : "countries";
        $isGeodbAdmin = $this->user->identity->getChandlerUser()->can("write")->model("openvk\\Web\\Models\\Entities\\GeodbCountry")->whichBelongsTo(0);

        if (in_array($mode, ["add_country", "editors", "editor"]))
            $this->assertPermission("openvk\\Web\\Models\\Entities\\GeodbCountry", "write", 0);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();

            $payload = [];

            switch ($mode) {
                case "add_country":
                    if (!$this->postParam("code") || !$this->postParam("flag") || !$this->postParam("name"))
                        $this->flashFail("err", tr("error"), "Заполнены не все обязательные поля");

                    if (!$isGeodbAdmin) $this->notFound();

                    if ((int)$this->queryParam("id")) {
                        $country = $this->countries->get((int)$this->queryParam("id"));
                    } else {
                        $country = new GeodbCountry;
                    }

                    $country->setCode($this->postParam("code"));
                    $country->setFlag($this->postParam("flag"));
                    $country->setName($this->postParam("name"));
                    $country->setNative_Name($this->postParam("native_name"));
                    $country->save($this->user->identity, $this->countries->getTable(), true);

                    $this->flashFail("succ", "Страна добавлена");
                    break;

                case "editors":
                    if (!$isGeodbAdmin) $this->notFound();

                    if ($this->postParam("q")) {
                        $editors = [];
                        $_editors = $this->editors->search($this->postParam("q"));

                        foreach ($_editors as $editor) {
                            $u = $editor[0]->getUser();
                            $r = [[
                                "id" => $u->getId(),
                                "name" => $u->getCanonicalName(),
                                "avatar" => $u->getAvatarURL("normal"),
                                "url" => $u->getURL(),
                            ],
                                []
                            ];

                            foreach ($editor[1] as $country) {
                                $r[1][] = [
                                    "id" => $country->getId(),
                                    "code" => $country->getCode(),
                                    "flag" => $country->getFlagURL(),
                                    "name" => $country->getCanonicalName(),
                                    "edu" => $country->isUserCanEditEducation($u->getId()),
                                    "cities" => $country->isUserCanEditCities($u->getId()),
                                ];
                            }

                            $editors[] = $r;
                        }

                        $this->returnJson(["editors" => $editors]);
                    } else {
                        if (!$this->postParam("link") || !$this->postParam("country"))
                            $this->flashFail("err", tr("error"), "Заполнены не все обязательные поля");

                        if (
                            (empty($this->postParam("can_access_edu")) && empty($this->postParam("can_access_cities")))
                            ||
                            ($this->postParam("can_access_edu") === 0 && $this->postParam("can_access_cities") === 0)
                        )
                            $this->returnJson(["success" => false, "error" => "Вы не можете добавить сотрудника без прав :("]);

                        $country = $this->countries->getByCode($this->postParam("country"));
                        if (!$country || $country->isDeleted())
                            $this->returnJson(["success" => false, "error" => "Страна не найдена"]);

                        $users_getter = is_numeric($this->postParam("link")) ? "get" : "getByAddress";
                        $user = (new Users)->$users_getter((int)$this->postParam("link") ?? $this->postParam("link"));

                        if (!$user)
                            $this->returnJson(["sucecss" => false, "error" => "Пользователь не найден"]);

                        $rights = $this->editors->getList($user->getId());
                        $_countries = [];
                        foreach ($rights as $right) {
                            foreach ($right[1] as $_country) {
                                if ($_country->getId() === $country->getId()) {
                                    $this->returnJson(["success" => false, "error" => "Пользователь уже добавлен в эту страну. Редактируйте существующую запись для изменения прав."]);
                                } else {
                                    $_countries[] = $_country;
                                }
                            }
                        }

                        $editor = new GeodbEditor;
                        $editor->setUid($user->getId());
                        $editor->setCountry($country->getId());
                        $editor->setEdu($this->postParam("can_access_edu") == "1");
                        $editor->setCities($this->postParam("can_access_cities") == "1");
                        $editor->save($this->user->identity, $this->editors->getTable());

                        $this->returnJson(["success" => true, "payload" => [
                            "id" => $country->getId(),
                            "user" => $user->getId(),
                            "name" => $country->getCanonicalName(),
                            "user_name" => $user->getCanonicalName(),
                            "code" => $country->getCode(),
                            "flag" => $country->getFlagURL(),
                            "edu" => $country->isUserCanEditEducation($user->getId()),
                            "cities" => $country->isUserCanEditCities($user->getId()),
                            "link" => $user->getURL(),
                            "avatar" => $user->getAvatarURL("normal"),
                            "user_exists" => (sizeof($_countries) > 0)
                        ]]);
                    }
                    break;

                case "editor":
                    if (!$isGeodbAdmin) $this->notFound();

                    if (!$this->queryParam("id"))
                        $this->returnJson(["success" => false, "error" => "ID пользователя не передан."]);

                    if (!$this->postParam("country"))
                        $this->returnJson(["success" => false, "error" => "Код страны не передан"]);

                    if (
                        (
                            (empty($this->postParam("can_access_edu")) && empty($this->postParam("can_access_cities")))
                            ||
                            ($this->postParam("can_access_edu") === 0 && $this->postParam("can_access_cities") === 0)
                        )
                        &&
                        !$this->queryParam("delete")
                    )
                        $this->returnJson(["success" => false, "error" => "Права не выбраны"]);

                    $country = $this->countries->getByCode($this->postParam("country"));

                    if (!$country || $country->isDeleted())
                        $this->returnJson(["success" => false, "error" => "Страна не найдена"]);

                    $user = (new Users)->get((int)$this->queryParam("id"));
                    if (!$user)
                        $this->returnJson(["success" => false, "error" => "Пользователь не найден"]);

                    if ($this->queryParam("delete")) {
                        $editor = $this->editors->getList($user->getId(), $country->getId());
                        if (!$editor)
                            $this->returnJson(["success" => false, "error" => "Пользователь не редактирует эту страну"]);

                        $this->logs->create($this->user->identity, "geodb_editors", 2, $editor[0], ["deleted" => 1]);
                        $editor[0]->delete();

                        if ($this->editors->getUserCountriesCount($user->getId()) === 0)
                            $payload["delete_user"] = 1;
                    } else if ($this->queryParam("edit")) {
                        $editor = $this->editors->getList($user->getId(), $country->getId());
                        if (!$editor)
                            $this->returnJson(["success" => false, "error" => "Пользователь не редактирует эту страну"]);

                        $edu = $this->postParam("can_access_edu") == 1;
                        $cities = $this->postParam("can_access_cities") == 1;

                        $editor = $this->editors->get($editor[0]->getId());
                        $editor->setEdu($edu);
                        $editor->setCities($cities);
                        $editor->save($this->user->identity, $this->editors->getTable());

                        $this->returnJson(["success" => true, "payload" => [
                            "edu" => $edu,
                            "cities" => $cities
                        ]]);
                    } else {
                        $rights = $this->editors->getList($user->getId());
                        foreach ($rights as $right) {
                            foreach ($right[1] as $_country) {
                                if ($_country->getId() === $country->getId()) {
                                    $this->returnJson(["success" => false, "error" => "Пользователь уже добавлен в эту страну. Редактируйте существующую запись для изменения прав."]);
                                }
                            }
                        }

                        $editor = new GeodbEditor;
                        $editor->setUid($user->getId());
                        $editor->setCountry($country->getId());
                        $editor->setEdu($this->postParam("can_access_edu") == 1);
                        $editor->setCities($this->postParam("can_access_cities") == 1);
                        $editor->save($this->user->identity, $this->editors->getTable());

                        $payload = [
                            "id" => $country->getId(),
                            "user" => $user->getId(),
                            "name" => $country->getCanonicalName(),
                            "user_name" => $user->getCanonicalName(),
                            "code" => $country->getCode(),
                            "flag" => $country->getFlagURL(),
                            "edu" => $country->isUserCanEditEducation($user->getId()),
                            "cities" => $country->isUserCanEditCities($user->getId()),
                        ];
                    }

                    $this->returnJson(["success" => true, "payload" => $payload]);
                    break;

                case "city":
                    if ($this->queryParam("delete") || $this->queryParam("restore")) {
                        if (!$isGeodbAdmin && $this->queryParam("restore")) $this->notFound();
                        $city = $this->cities->get((int)$this->queryParam("id"));
                        if (!$city)
                            $this->returnJson(["success" => false, "error" => "Город не найден"]);

                        $city->setDeleted($this->queryParam("delete") ? 1 : 0);
                        $city->save($this->user->identity, $this->cities->getTable());
                        $this->returnJson(["success" => true]);
                    } else {
                        $city = $this->cities->get((int)$this->queryParam("id"));
                        if (!$city)
                            $this->notFound();

                        if (!$this->postParam("name") || !$this->postParam("native_name"))
                            $this->flashFail("err", tr("error"), "Заполнены не все обязательные поля");

                        $city->setName($this->postParam("name"));
                        $city->setNative_Name($this->postParam("native_name"));
                        $city->save($this->user->identity, $this->cities->getTable());

                        $this->flashFail("succ", "Город сохранен");
                    }
                    break;

                case "add_city":
                    $country = $this->countries->get((int)$this->queryParam("id"));

                    if (!$country || $country->isDeleted())
                        $this->redirect("/editdb?act=countries");

                    if (!$this->postParam("name") || !$this->postParam("native_name"))
                        $this->flashFail("err", tr("error"), "Заполнены не все обязательные поля");

                    $city = new GeodbCity;
                    $city->setCountry($country->getId());
                    $city->setName($this->postParam("name"));
                    $city->setNative_Name($this->postParam("native_name"));
                    $city->save($this->user->identity, $this->cities->getTable());

                    $this->flashFail("succ", "Город добавлен");
                    break;

                case "add_edu":
                    $country = $this->countries->get((int)$this->queryParam("id"));

                    if (!$country || $country->isDeleted())
                        $this->redirect("/editdb?act=countries");

                    $city = $this->cities->get((int)$this->postParam("city-id"));
                    if (!$city || ($city && $city->getCountry()->getId() !== $country->getId()))
                        $this->redirect("/editdb?act=country&id=" . $country->getId());

                    $view = in_array($this->queryParam("view"), ["schools", "universities"]) ? $this->queryParam("view") : "schools";

                    if (!$this->postParam("name"))
                        $this->flashFail("err", tr("error"), "Заполнены не все обязательные поля");

                    $item = ($view === "schools") ? new GeodbSchool : new GeodbUniversity;
                    $item->setCountry($country->getId());
                    $item->setCity($city->getId());
                    $item->setName($this->postParam("name"));
                    $item->save($this->user->identity, $this->education->getTable($view));

                    $this->flashFail("succ", ($view === "schools" ? "Школа добавлена" : "Университет добавлен"));
                    break;

                case "country":
                    if (!$isGeodbAdmin) $this->notFound();

                    $country = $this->countries->get((int)$this->queryParam("id"));
                    if (!$country || ($country->isDeleted() && !$this->queryParam("restore")))
                        $this->returnJson(["success" => false, "error" => "Страна не найдена"]);

                    if ($this->queryParam("delete") || $this->queryParam("restore")) {
                        if (!$isGeodbAdmin) $this->notFound();

                        $country->setDeleted($this->queryParam("delete") ? 1 : 0);
                        $country->save($this->user->identity, $this->countries->getTable());
                        $this->returnJson(["success" => true, "payload" => $country->getId()]);
                    } else {
                        $city_id = NULL;
                        if ($this->queryParam("city")) {
                            $city = $this->cities->get((int)$this->queryParam("city"));
                            if ($city && ($city->getCountry()->getId() === $country->getId())) {
                                $city_id = $city->getId();
                            }
                        }

                        if ($this->queryParam("edu")) {
                            $view = in_array($this->queryParam("view"), ["schools", "universities"]) ? $this->queryParam("view") : "schools";
                            if ($view === "schools") {
                                $schools = $this->education->getSchools($country->getId(), $city_id);
                                $response = [];
                                foreach ($schools as $school) {
                                    $response[] = $school->getSimplified();
                                }
                            } else {
                                $universities = $this->education->getUniversities($country->getId(), $city_id);
                                $response = [];

                                if ($this->queryParam("uid") && $this->queryParam("n") === "faculties") {
                                    foreach ($universities as $university) {
                                        if ($university->getId() === (int)$this->queryParam("uid")) {
                                            $_faculties = $university->getFaculties();
                                            foreach ($_faculties as $faculty) {
                                                $_faculty = $faculty->getSimplified();
                                                $specializations = $faculty->getSpecializations(false, true);
                                                $_faculty["specializations"] = [];
                                                foreach ($specializations as $specialization) {
                                                    $_faculty["specializations"][] = $specialization;
                                                }
                                                $response[] = $_faculty;
                                            }
                                        }
                                    }
                                } else if ($this->queryParam("fid") && $this->queryParam("n") === "specializations") {
                                    $faculty = $this->faculties->get((int)$this->queryParam("fid"));
                                    $response = iterator_to_array($faculty->getSpecializations(false, true));
                                } else {
                                    foreach ($universities as $university) {
                                        $_university = $university->getSimplified();
                                        $_faculties = $university->getFaculties();
                                        $_university["faculties"] = [];
                                        foreach ($_faculties as $key => $value) {
                                            $_faculty = $value->getSimplified();
                                            $specializations = $value->getSpecializations(false, true);
                                            $_faculty["specializations"] = [];
                                            foreach ($specializations as $specialization) {
                                                $_faculty["specializations"][] = $specialization;
                                            }
                                            $_university["faculties"][$key] = $_faculty;
                                        }

                                        $response[] = $_university;
                                    }
                                }
                            }

                            $response = ["success" => true, "list" => $response];
                            if ($city_id) $response["city"] = $city->getNativeName();
                            $this->returnJson($response);
                        }
                    }
                    break;

                case "school":
                    $id = ((int)$this->postParam("id") ?: (int)$this->queryParam("id"));
                    $school = $this->education->getSchool($id);
                    if (!$school)
                        $this->returnJson(["success" => false, "error" => "Школа не найдена"]);

                    if ($this->queryParam("delete") || $this->queryParam("restore")) {
                        if (!$isGeodbAdmin && $this->queryParam("restore")) $this->notFound();
                        $school->setDeleted($this->queryParam("delete") ? 1 : 0);
                    } else {
                        if ((int)$this->postParam("city-id") !== $school->getCity()->getId()) {
                            $city = $this->cities->get((int)$this->postParam("city-id"));
                            if ($city) {
                                $school->setCity($city->getId());
                            }
                        }

                        $school->setName($this->postParam("name"));
                    }

                    $school->save($this->user->identity, $this->education->getTable("schools"));
                    if ($this->postParam("id"))
                        $this->returnJson(["success" => true, "payload" => $school->getId()]);
                    else
                        $this->flashFail("succ", "Изменения сохранены");
                    break;

                case "university":
                    $id = ((int)$this->postParam("id") ?: (int)$this->queryParam("id"));
                    $university = $this->education->getUniversity($id);
                    if (!$university)
                        $this->returnJson(["success" => false, "error" => "Университет не найден"]);

                    if ($this->queryParam("delete") || $this->queryParam("restore")) {
                        if (!$isGeodbAdmin && $this->queryParam("restore")) $this->notFound();
                        $university->setDeleted($this->queryParam("delete") ? 1 : 0);
                    } else {
                        if ((int)$this->postParam("city-id") !== $university->getCity()->getId()) {
                            $city = $this->cities->get((int)$this->postParam("city-id"));
                            if ($city) {
                                $university->setCity($city->getId());
                            }
                        }

                        $university->setName($this->postParam("name"));
                    }

                    $university->save($this->user->identity, $this->education->getTable("universities"));
                    if ($this->postParam("id"))
                        $this->returnJson(["success" => true, "payload" => $university->getId()]);
                    else
                        $this->flashFail("succ", "Изменения сохранены");
                    break;

                case "add_faculty":
                    $university = $this->education->getUniversity((int)$this->queryParam("uid"));

                    if (!$university || $university->isDeleted())
                        $this->redirect("/editdb?act=countries");

                    if (!$this->postParam("name"))
                        $this->returnJson(["success" => false, "error" => "Заполнены не все поля"]);

                    $faculty = new GeodbFaculty;
                    $faculty->setUniversity($university->getId());
                    $faculty->setName($this->postParam("name"));
                    $faculty->save($this->user->identity, $this->faculties->getTable());

                    $this->returnJson([
                        "success" => true,
                        "payload" => $faculty->getSimplified(),
                        "reload" => count(iterator_to_array($university->getFaculties())) === 1,
                    ]);
                    break;

                case "faculty":
                    $deleted = ($isGeodbAdmin && !$this->queryParam("delete"));
                    if ($this->queryParam("delete") || $this->queryParam("restore")) {
                        if (!$isGeodbAdmin && $this->queryParam("restore")) $this->notFound();

                        $faculty = $this->faculties->get((int)$this->postParam("id"));
                        if (!$faculty || (!$deleted && $faculty->isDeleted()))
                            $this->returnJson(["success" => false, "error" => "Факультет не найден"]);

                        $faculty->setDeleted($this->queryParam("delete") ? 1 : 0);
                        $faculty->save($this->user->identity, $this->faculties->getTable());

                        $this->returnJson([
                            "success" => true,
                            "reload" => count(iterator_to_array($faculty->getUniversity()->getFaculties($deleted))) <= 0,
                            "payload" => $faculty->getId()
                        ]);
                    } else {
                        $faculty = $this->faculties->get((int)$this->queryParam("id"));
                        if (!$faculty)
                            $this->notFound();

                        if (!$this->postParam("name"))
                            $this->returnJson(["success" => false, "error" => "Заполнены не все обязательные поля"]);

                        $faculty->setName($this->postParam("name"));
                        $faculty->save($this->user->identity, $this->faculties->getTable());

                        $this->returnJson(["success" => true, "payload" => $faculty->getId()]);
                    }
                    break;

                case "specializations":
                    $faculty = $this->faculties->get((int)$this->postParam("fid"));
                    if (!$faculty || $faculty->isDeleted())
                        $this->returnJson(["success" => false, "error" => "Факультет не найден"]);

                    $this->returnJson([
                        "success" => true,
                        "list" => iterator_to_array($this->specializations->getList($faculty->getId(), ($isGeodbAdmin && $this->queryParam("deleted")), true))
                    ]);
                    break;

                case "specialization":
                    $deleted = ($isGeodbAdmin && !$this->queryParam("delete"));
                    if ($this->queryParam("delete") || $this->queryParam("restore")) {
                        if (!$isGeodbAdmin && $this->queryParam("restore")) $this->notFound();

                        $specialization = $this->specializations->get((int)$this->postParam("id"));
                        if (!$specialization || (!$deleted && $specialization->isDeleted()))
                            $this->returnJson(["success" => false, "error" => "Факультет не найден"]);

                        $specialization->setDeleted($this->queryParam("delete") ? 1 : 0);
                        $specialization->save($this->user->identity, $this->specializations->getTable());

                        $this->returnJson([
                            "success" => true,
                            "payload" => $specialization->getSimplified()
                        ]);
                    } else {
                        $specialization = $this->specializations->get((int)$this->queryParam("id"));
                        if (!$specialization)
                            $this->notFound();

                        if (!$this->postParam("name"))
                            $this->returnJson(["success" => false, "error" => "Заполнены не все обязательные поля"]);

                        $specialization->setName($this->postParam("name"));
                        $specialization->save($this->user->identity, $this->specializations->getTable());

                        $this->returnJson(["success" => true, "payload" => $specialization->getId()]);
                    }
                    break;

                case "add_specialization":
                    $faculty = $this->faculties->get((int)$this->queryParam("fid"));

                    if (!$faculty || $faculty->isDeleted())
                        $this->returnJson(["success" => false, "error" => "Факультет не найден"]);

                    if (!$this->postParam("name"))
                        $this->returnJson(["success" => false, "error" => "Заполнены не все поля"]);

                    $specialization = new GeodbSpecialization;
                    $specialization->setFaculty($faculty->getId());
                    $specialization->setName($this->postParam("name"));
                    $specialization->save($this->user->identity, $this->specializations->getTable());

                    $this->returnJson([
                        "success" => true,
                        "payload" => $specialization->getSimplified(),
                    ]);
                    break;

                case "requests":
                    $rid = (int)$this->postParam("rid");
                    $view = in_array($this->queryParam("tab"), ["cities", "schools", "universities", "faculties", "specializations"]) ? $this->queryParam("tab") : ($this->template->can_view_cities ? "cities" : "schools");
                    $item = NULL;
                    if (!in_array($view, ["schools", "universities"])) {
                        $repo = [
                            "cities" => "openvk\\Web\\Models\\Repositories\\GeodbCities",
                            "faculties" => "openvk\\Web\\Models\\Repositories\\GeodbFaculties",
                            "specializations" => "openvk\\Web\\Models\\Repositories\\GeodbSpecializations"
                        ][$view];
                        $item = (new $repo)->get($rid);
                    } else {
                        $repo = "openvk\\Web\\Models\\Repositories\\GeodbEducation";
                        if ($view === "schools") {
                            $item = $this->education->getSchool($rid);
                        } else {
                            $item = $this->education->getUniversity($rid);
                        }
                    }

                    if (!$item)
                        $this->returnJson(["success" => false, "error" => "Заявка не найдена"]);

                    $names = [
                        "cities" => "Вашем городе",
                        "schools" => "Вашей школе",
                        "universities" => "Вашем вузе",
                        "faculties" => "Вашем факультете",
                        "specializations" => "Вашей специализации"
                    ];

                    if ($this->queryParam("edit")) {
                        $item->setName($this->postParam("name"));
                        if ($view === "cities") {
                            $item->setNative_Name($this->postParam("native_name"));
                        }
                        $item->save($this->user->identity, (new $repo)->getTable($view));

                        $this->returnJson(["success" => true, "payload" => [$item->getName(), ($view === "cities" ? $item->getNativeName() : "")]]);
                    } else if ($this->queryParam("decline")) {
                        $user = $item->getRequestSender();
                        $user->adminNotify(($user->isFemale() ? "Дорогая " : "Дорогой ") . $user->getFirstName() . "!\n\nМы рассмотрели Вашу заявку. К сожалению, мы не смогли найти информацию о " . $names[$view] . " (" . $item->getName() . "). Пожалуйста, уточните данные и подайте заявку ещё раз.\n\nЭто сообщение отправлено автоматически. Пожалуйста, не отвечайте на него. Если у Вас есть вопросы, напишите нам здесь: https://$_SERVER[HTTP_HOST]/support?act=new.");
                        $item->delete();
                        $this->returnJson(["success" => true, "payload" => $rid]);
                    } else if ($this->queryParam("accept")) {
                        $user = $item->getRequestSender();
                        $item->setIs_Request(0);
                        $item->save($this->user->identity, (new $repo)->getTable($view));
                        $user->adminNotify(($user->isFemale() ? "Дорогая " : "Дорогой ") . $user->getFirstName() . "!\n\nМы рассмотрели Вашу заявку и добавили информацию о " . $names[$view] . " (" . $item->getName() . ") в базу.\n\nЭто сообщение отправлено автоматически. Пожалуйста, не отвечайте на него. Если у Вас есть вопросы, напишите нам здесь: https://$_SERVER[HTTP_HOST]/support?act=new.");
                        $this->returnJson(["success" => true, "payload" => $item->getId()]);
                    } else {
                        $this->returnJson(["success" => false, "error" => "Заявка не найдена"]);
                    }
                    break;

                default:
                    $this->notFound();
                    break;
            }
        } else {
            $this->template->mode = $mode;
            $this->template->countries = $isGeodbAdmin
                ? iterator_to_array($this->countries->getList(false, ($this->queryParam("deleted") && $mode === "countries")))
                : iterator_to_array($this->editors->getUserCountries($this->user->identity->getId()));

            $this->template->isGeodbAdmin = $isGeodbAdmin;

            switch ($mode) {
                case "countries":
                    $this->template->_template = "Geodb/Countries.xml";
                    $this->template->title = "Страны";
                    $this->template->can_add_country = $isGeodbAdmin;
                    $this->template->can_view_deleted = $isGeodbAdmin;
                    $this->template->is_deleted = ($isGeodbAdmin && $this->queryParam("deleted"));

                    if (count($this->template->countries) <= 0) {
                        if ($this->template->is_deleted) {
                            $this->redirect("/editdb?act=countries");
                        } else {
                            $this->flashFail("err", "Страны не найдены", ($isGeodbAdmin ? "Если Вы впервые редактируете геодб, сначала создайте страну <a href='/editdb?act=add_country'>здесь</a>" : ""));
                        }
                    }
                    break;

                case "add_country":
                    $this->template->_template = "Geodb/AddCountry.xml";
                    $this->template->country = $this->countries->get((int)$this->queryParam("id"));
                    break;

                case "editors":
                    $this->template->_template = "Geodb/Editors.xml";
                    $this->template->title = "Редакторы базы";
                    $this->template->editors = $this->editors->getList(NULL, NULL, $this->queryParam("q"));
                    break;

                case "editor":
                    $this->template->title = "Редакторы базы";
                    $this->template->mode = "editors";
                    break;

                case "country":
                    $this->template->_template = "Geodb/Country.xml";
                    $country = $this->countries->get((int)$this->queryParam("id"));
                    $is_edu = $this->queryParam("edu") == 1;

                    if (!$country || $country->isDeleted())
                        $this->redirect("/editdb?act=countries");

                    $this->template->can_edit_edu = ($isGeodbAdmin || $country->isUserCanEditEducation($this->user->identity->getId()));
                    $this->template->can_edit_cities = ($isGeodbAdmin || $country->isUserCanEditCities($this->user->identity->getId()));

                    $this->template->country = $country;
                    $this->template->is_edu = $is_edu;

                    if (!$is_edu) {
                        if (!$this->template->can_edit_cities)
                            if ($this->template->can_edit_edu)
                                $this->redirect("/editdb?act=country&id=" . $country->getId() . "&edu=1");
                            else
                                $this->notFound();

                        $this->template->cities = iterator_to_array($this->cities->getList($country->getId(), ($isGeodbAdmin && $this->queryParam("deleted"))));
                    } else {
                        if (!$this->template->can_edit_edu)
                            $this->notFound();

                        $view = in_array($this->queryParam("view"), ["schools", "universities"]) ? $this->queryParam("view") : "schools";
                        $this->template->view = $view;

                        $city_id = NULL;
                        if ($this->queryParam("city")) {
                            $city = $this->cities->get((int)$this->queryParam("city"));
                            if ($city && ($city->getCountry()->getId() === $country->getId())) {
                                $this->template->city = $city;
                                $city_id = $city->getId();
                            }
                        }

                        if ($view === "schools") {
                            $this->template->schools = iterator_to_array($this->education->getSchools($country->getId(), $city_id, ($isGeodbAdmin && $this->queryParam("deleted"))));
                        } else {
                            $this->template->universities = iterator_to_array($this->education->getUniversities($country->getId(), $city_id, ($isGeodbAdmin && $this->queryParam("deleted"))));
                        }
                    }
                    $this->template->can_view_deleted = $isGeodbAdmin;
                    $this->template->is_deleted = ($isGeodbAdmin && $this->queryParam("deleted"));

                    $this->template->title = $is_edu ? "Образование" : "Города";
                    break;

                case "add_city":
                    $this->template->_template = "Geodb/AddCity.xml";
                    $country = $this->countries->get((int)$this->queryParam("id"));

                    if (!$country || $country->isDeleted())
                        $this->redirect("/editdb?act=countries");

                    $this->template->country = $country;
                    $this->template->title = "Добавить город";
                    break;

                case "city":
                    $this->template->_template = "Geodb/City.xml";
                    $city = $this->cities->get((int)$this->queryParam("id"));
                    if (!$city)
                        $this->notFound();

                    $country = $city->getCountry();

                    $this->template->country = $country;
                    $this->template->city = $city;
                    $this->template->title = "Город";
                    break;

                case "university":
                    $this->template->_template = "Geodb/School.xml";
                    $university = $this->education->getUniversity((int)$this->queryParam("id"));
                    if (!$university)
                        $this->notFound();

                    $country = $university->getCountry();

                    $this->template->country = $country;
                    $this->template->school = $university;
                    $this->template->title = "Университет";
                    $this->template->can_view_deleted = $isGeodbAdmin;
                    $this->template->is_deleted = ($isGeodbAdmin && $this->queryParam("deleted"));
                    $this->template->faculties = iterator_to_array($university->getFaculties($this->template->is_deleted));
                    break;

                case "add_edu":
                    $this->template->_template = "Geodb/AddEdu.xml";
                    $country = $this->countries->get((int)$this->queryParam("id"));

                    if (!$country || $country->isDeleted())
                        $this->redirect("/editdb?act=countries");

                    $this->template->country = $country;
                    $view = in_array($this->queryParam("view"), ["schools", "universities"]) ? $this->queryParam("view") : "schools";
                    $this->template->view = $view;
                    $titles = [
                        "schools" => "школу",
                        "universities" => "университет"
                    ];
                    $this->template->title = "Добавить " . $titles[$this->queryParam("view")];
                    break;

                case "school":
                    $this->template->_template = "Geodb/School.xml";
                    $school = $this->education->getSchool((int)$this->queryParam("id"));
                    if (!$school)
                        $this->notFound();

                    $country = $school->getCountry();

                    $this->template->country = $country;
                    $this->template->school = $school;
                    $this->template->title = "Школа";
                    break;

                case "requests":
                    $this->template->_template = "Geodb/Requests.xml";

                    if ($this->queryParam("cid")) {
                        $country = $this->countries->get((int)$this->queryParam("cid"));
                        if (!$country || $country->isDeleted())
                            $this->flashFail("err", "Страна не найдена", ($isGeodbAdmin ? "Если Вы впервые редактируете геодб, сначала создайте страну <a href='/editdb?act=add_country'>здесь</a>" : ""));

                        $this->template->current_country = $country;

                        $this->template->can_view_cities = ($isGeodbAdmin || $country->isUserCanEditCities($this->user->identity->getId()));
                        $this->template->can_view_education = ($isGeodbAdmin || $country->isUserCanEditEducation($this->user->identity->getId()));

                        if (!$this->template->can_view_cities && !$this->template->can_view_education)
                            $this->notFound();

                        $view = in_array($this->queryParam("tab"), ["cities", "schools", "universities", "faculties", "specializations"]) ? $this->queryParam("tab") : ($this->template->can_view_cities ? "cities" : "schools");
                        $this->template->mode = $view;

                        $requests = [];
                        $_requests = [];

                        if (!in_array($view, ["schools", "universities"])) {
                            $repo = [
                                "cities" => "openvk\\Web\\Models\\Repositories\\GeodbCities",
                                "faculties" => "openvk\\Web\\Models\\Repositories\\GeodbFaculties",
                                "specializations" => "openvk\\Web\\Models\\Repositories\\GeodbSpecializations"
                            ][$view];
                            $cid = $country->getId();
                            if (in_array($view, ["specializations"])) $cid = $cid * -1;
                            $_requests = (new $repo)->getList($cid, false, false, true);
                        } else {
                            if ($view === "schools") {
                                $_requests = $this->education->getSchools($country->getId(), NULL, false, false, true);
                            } else {
                                $_requests = $this->education->getUniversities($country->getId(), NULL, false, false, true);
                            }
                        }

                        foreach ($_requests as $_item) {
                            $requests[] = [$_item, $_item->getRequestSender()];
                        }

                        $this->template->requests = $requests;
                        $this->template->type = ["cities" => "город", "schools" => "школу", "universities" => "университет", "faculties" => "факультет", "specialization" => "специализацию"][$view];
                    } else {
                        $this->redirect("/editdb?act=requests&cid=1");
                    }
                    break;

                case "logs":
                    $this->template->_template = "Geodb/Logs.xml";

                    if (!$isGeodbAdmin)
                        $this->notFound();

                    $logs = $this->logs->getList((int)$this->queryParam("uid"));
                    $this->template->logs = iterator_to_array($logs);
                    $this->template->count = count($this->template->logs);
                    break;
            }
        }
    }

    function renderForUser(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") $this->notFound();

        $this->assertUserLoggedIn();
        $this->assertNoCSRF();

        switch ($this->queryParam("act")) {
            case "countries":
                $c = $this->countries->getList(TRUE);
                $list = [];

                foreach ($c as $country) {
                    if ($this->postParam("q")) {
                        $q = trim(mb_strtolower($this->postParam("q")));

                        $name = trim(mb_strtolower($country["name"]));
                        $native_name = trim(mb_strtolower($country["native_name"]));

                        if ($q == $name || $q == $native_name) {
                            $list[] = $country;
                            break;
                        }

                        preg_match('/' . $q . '/i', $name, $name_matches);
                        preg_match('/' . $q . '/i', $native_name, $native_name_matches);

                        if (sizeof($name_matches) > 0 || sizeof($native_name_matches) > 0) {
                            $list[] = $country;
                        }
                    } else {
                        $list[] = $country;
                    }
                }

                $this->returnJson(["list" => $list]);
                break;

            case "cities":
                $country = $this->countries->get((int)$this->postParam("country"));
                if (!$country || $country->isDeleted()) $this->returnJson(["success" => false, "error" => "Страна не найдена"]);

                $cities = $this->cities->getList($country->getId(), false, true);
                $list = [];

                foreach ($cities as $city) {
                    if ($this->postParam("q")) {
                        $q = trim(mb_strtolower($this->postParam("q")));

                        $name = trim(mb_strtolower($city["name"]));
                        $native_name = trim(mb_strtolower($city["native_name"]));

                        if ($q == $name || $q == $native_name) {
                            $list[] = $city;
                            break;
                        }

                        preg_match('/' . $q . '/i', $name, $name_matches);
                        preg_match('/' . $q . '/i', $native_name, $native_name_matches);

                        if (sizeof($name_matches) > 0 || sizeof($native_name_matches) > 0) {
                            $list[] = $city;
                        }
                    } else {
                        $list[] = $city;
                    }
                }

                $this->returnJson(["success" => true, "list" => $list]);
                break;

            case "schools":
                $city = $this->cities->get((int)$this->postParam("city"));
                if (!$city) $this->notFound();

                $schools = $this->education->getSchools($city->getCountry()->getId(), $city->getId());
                break;

            case "country":
                $country = $this->countries->get((int)$this->queryParam("id"));
                if (!$country || $country->isDeleted()) $this->returnJson(["success" => false, "error" => "Страна не найдена"]);

                $city_id = NULL;
                if ($this->queryParam("city")) {
                    $city = $this->cities->get((int)$this->queryParam("city"));
                    if ($city && ($city->getCountry()->getId() === $country->getId())) {
                        $city_id = $city->getId();
                    }
                }

                if ($this->queryParam("edu")) {
                    $view = in_array($this->queryParam("view"), ["schools", "universities", "faculties"]) ? $this->queryParam("view") : "schools";
                    if ($view === "schools") {
                        $schools = $this->education->getSchools($country->getId(), $city_id);
                        $response = [];
                        foreach ($schools as $school) {
                            $response[] = $school->getSimplified();
                        }
                    } else if ($view === "faculties") {
                        $university = $this->education->getUniversity((int)$this->queryParam("uid"));
                        if (!$university) $this->returnJson(["success" => false, "error" => "Университет не найден"]);

                        $faculties = iterator_to_array($this->faculties->getList((int)$this->queryParam("uid"), false, true));
                        $response = $faculties;
                    } else {
                        $universities = $this->education->getUniversities($country->getId(), $city_id);
                        $response = [];

                        if ($this->queryParam("uid") && $this->queryParam("n") === "faculties") {
                            foreach ($universities as $university) {
                                if ($university->getId() === (int)$this->queryParam("uid")) {
                                    $_faculties = $university->getFaculties();
                                    foreach ($_faculties as $faculty) {
                                        $_faculty = $faculty->getSimplified();
                                        $specializations = $faculty->getSpecializations(false, true);
                                        $_faculty["specializations"] = [];
                                        foreach ($specializations as $specialization) {
                                            $_faculty["specializations"][] = $specialization;
                                        }
                                        $response[] = $_faculty;
                                    }
                                }
                            }
                        } else if ($this->queryParam("fid") && $this->queryParam("n") === "specializations") {
                            $faculty = $this->faculties->get((int)$this->queryParam("fid"));
                            $response = iterator_to_array($faculty->getSpecializations(false, true));
                        } else {
                            foreach ($universities as $university) {
                                $_university = $university->getSimplified();
                                $_faculties = $university->getFaculties();
                                $_university["faculties"] = [];
                                foreach ($_faculties as $key => $value) {
                                    $_faculty = $value->getSimplified();
                                    $specializations = $value->getSpecializations(false, true);
                                    $_faculty["specializations"] = [];
                                    foreach ($specializations as $specialization) {
                                        $_faculty["specializations"][] = $specialization;
                                    }
                                    $_university["faculties"][$key] = $_faculty;
                                }

                                $response[] = $_university;
                            }
                        }
                    }

                    $response = ["success" => true, "list" => $response];
                    if ($city_id) $response["city"] = $city->getNativeName();
                    $this->returnJson($response);
                }
                break;

            case "new_request":
                $view = in_array($this->queryParam("tab"), ["cities", "schools", "universities", "faculties", "specializations"]) ? $this->queryParam("tab") : "cities";

                $country = $this->countries->get((int)$this->postParam("country"));
                if (!$country || $country->isDeleted())
                    $this->returnJson(["success" => false, "error" => "Страна не найдена"]);

                $models = [
                    "cities" => "openvk\\Web\\Models\\Entities\\GeodbCity",
                    "schools" => "openvk\\Web\\Models\\Entities\\GeodbSchool",
                    "universities" => "openvk\\Web\\Models\\Entities\\GeodbUniversity",
                    "faculties" => "openvk\\Web\\Models\\Entities\\GeodbFaculty",
                    "specializations" => "openvk\\Web\\Models\\Entities\\GeodbSpecialization"
                ];

                $repos = [
                    "cities" => "openvk\\Web\\Models\\Repositories\\GeodbCities",
                    "schools" => "openvk\\Web\\Models\\Repositories\\GeodbEducation",
                    "universities" => "openvk\\Web\\Models\\Repositories\\GeodbEducation",
                    "faculties" => "openvk\\Web\\Models\\Repositories\\GeodbFaculties",
                    "specializations" => "openvk\\Web\\Models\\Repositories\\GeodbSpecializations",
                ];

                if (!$this->postParam("name"))
                    $this->returnJson(["success" => false, "error" => "Вы не ввели название " . ($view !== "cities" ? "" : "на английском")]);

                if ($view === "cities" && !$this->postParam("native_name"))
                    $this->returnJson(["success" => false, "error" => "Вы не ввели родное название"]);

                $item = new $models[$view];
                $item->setName($this->postParam("name"));

                if ($view !== "cities") {
                    $city = $this->cities->get((int)$this->postParam("city"));
                    if (!$city)
                        $this->returnJson(["success" => false, "error" => "Город не найден"]);

                    if (in_array($view, ["faculties", "specializations"])) {
                        $university = $this->education->getUniversity((int)$this->postParam("uid"));
                        if (!$university)
                            $this->returnJson(["success" => false, "error" => "Университет не найден"]);

                        if ($view === "faculties") $item->setUniversity($university->getId());

                        if ($view === "specializations") {
                            $faculty = $this->faculties->get((int)$this->postParam("fid"));
                            if (!$faculty)
                                $this->returnJson(["success" => false, "error" => "Факультет не найден"]);

                            $item->setFaculty((int)$this->postParam("fid"));
                            $item->setCountry($country->getId());
                        }
                    } else {
                        $item->setCountry($country->getId());
                        $item->setCity($city->getId());
                    }
                } else {
                    $item->setCountry($country->getId());
                    $item->setNative_Name($this->postParam("native_name") ?? "");
                }

                $item->setIs_Request($this->user->identity->getId());
                $item->save($this->user->identity, (new $repos[$view])->getTable($view));
                $this->returnJson(["success" => true]);
                break;

            default:
                $this->returnJson(["success" => false, "error" => "Неизвестная ошибка"]);
                break;
        }
    }
}
