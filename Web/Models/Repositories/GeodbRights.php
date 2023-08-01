<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;
use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\GeodbCountry;
use openvk\Web\Models\Entities\GeodbEditor;

class GeodbRights
{
    private $context;
    private $editors;

    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->editors  = $this->context->table("geodb_editors");
    }

    private function toGeodbRight(?ActiveRow $ar): ?GeodbEditor
    {
        return is_null($ar) ? NULL : new GeodbEditor($ar);
    }

    function get(int $id): ?GeodbEditor
    {
        return $this->toGeodbRight($this->editors->get($id));
    }

    function getList(?int $uid = NULL, ?int $cid = NULL, ?string $q = NULL): array
    {
        $filter = ["deleted" => 0];
        if ($uid) $filter["uid"] = $uid;
        if ($cid) $filter["country"] = $cid;

        if ($uid && $cid) {
            $editor = $this->toGeodbRight($this->context->table("geodb_editors")->where($filter)->fetch());
            if (!$editor) return [];

            return [$editor, $editor->getCountry()];
        }

        $users = [];
        $editors = [];

        $rights = $this->context->table("geodb_editors")->where($filter);

        foreach ($rights as $editor) {
            $editor = $this->toGeodbRight($editor);
            if (in_array($editor->getUser()->getId(), $users)) {
                foreach ($editors as $key => $value) {
                    if ($value[0]->getUser()->getId() === $editor->getUser()->getId()) {
                        $editors[$key][1][] = $editor->getCountry();
                    }
                }
            } else {
                if ($q) {
                    $_editors = $editors;
                    foreach ($_editors as $key => $value) {
                        $name = trim(mb_strtolower($value[0]->getUser()->getCanonicalName()));
                        $name_matches = [];
                        preg_match('/' . $q . '/i', $name, $name_matches);

                        if (!str_contains($name, trim(mb_strtolower($q))) && count($name_matches) === 0)
                            continue;

                        $editors[] = [$editor, [$editor->getCountry()]];
                        $users[] = $editor->getUser()->getId();
                    }
                } else {
                    $editors[] = [$editor, [$editor->getCountry()]];
                    $users[] = $editor->getUser()->getId();
                }
            }
        }

        return $editors;
    }

    function getUserCountriesCount(int $user): int
    {
        return $this->context->table("geodb_editors")->where(["uid" => $user, "deleted" => 0])->count();
    }

    function getUserCountries(int $user): \Traversable
    {
        foreach ($this->context->table("geodb_editors")->where(["uid" => $user, "deleted" => 0]) as $editor) {
            $editor = new GeodbEditor($editor);
            if (!$editor->getCountry()->isDeleted()) {
                yield $editor->getCountry();
            }
        }
    }

    function getCountryPermission(int $user, int $country): ?GeodbEditor
    {
        return $this->toGeodbRight($this->context->table("geodb_editors")->where(["uid" => $user, "country" => $country, "deleted" => 0])->fetch());
    }

    function search(string $q): array
    {
        $ids = [];
        $r = [];

        foreach ($this->editors->where("deleted", 0) as $_editor) {
            $e = (new GeodbEditor($_editor));
            $u = $e->getUser();
            if (in_array($u->getId(), $ids)) {
                foreach ($r as $key => $value) {
                    if ($value[0]->getUser()->getId() === $u->getId()) {
                        $r[$key][1][] = $e->getCountry();
                    }
                }
            } else {
                $name = trim(mb_strtolower($u->getCanonicalName()));
                $name_matches = [];
                preg_match('/' . $q . '/i', $name, $name_matches);

                if (!str_contains($name, trim(mb_strtolower($q))) && count($name_matches) === 0) continue;
                $ids[] = $u->getId();
                $r[] = [$e, [$e->getCountry()]];
            }
        }

        return $r;
    }

    function getRequestsCount(): int
    {
        return (new GeodbCities)->getRequestsCount()
            + (new GeodbEducation)->getSchoolsRequestsCount()
            + (new GeodbEducation)->getUniversitiesRequestsCount()
            + (new GeodbFaculties)->getRequestsCount()
            + (new GeodbSpecializations)->getRequestsCount();
    }

    function getTable()
    {
        return $this->editors;
    }
}
