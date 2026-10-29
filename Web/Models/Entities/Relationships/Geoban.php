<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Relationships;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\ModelCodes;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class Geoban extends RowModel
{
    protected $tableName = "geoblocks";

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getOwner(): ?User
    {
        return (new Users())->get($this->getRecord()->owner);
    }

    public function getModelCode(): int
    {
        return (int) $this->getRecord()->modelCode;
    }

    public function getModelId(): int
    {
        return (int) $this->getRecord()->modelId;
    }

    public function getModelClass(): ?string
    {
        $reverse = array_flip(ModelCodes::get());

        return $reverse[$this->getModelCode()] ?? null;
    }

    public function getModelName(): string
    {
        $class = $this->getModelClass();
        if (is_null($class)) {
            return (string) $this->getModelCode();
        }

        return substr($class, (int) strrpos($class, "\\") + 1);
    }

    public function getReason(): ?string
    {
        return $this->getRecord()->reason;
    }

    public function getCreated(): int
    {
        return (int) $this->getRecord()->created;
    }

    public function getEdited(): ?int
    {
        $edited = $this->getRecord()->edited;

        return is_null($edited) ? null : (int) $edited;
    }

    public function getTime(): ?string
    {
        return $this->getRecord()->time;
    }

    public function getPlaces(): array
    {
        $raw = trim((string) $this->getRecord()->places);
        if ($raw === "") {
            return [];
        }

        return array_values(array_filter(array_map("trim", explode(",", $raw))));
    }

    public function getPlacesRaw(): string
    {
        return implode(", ", $this->getPlaces());
    }

    public function setOwner(User $user): void
    {
        $this->stateChanges("owner", $user->getId());
    }

    public function setModelCode(int $code): void
    {
        $this->stateChanges("modelCode", $code);
    }

    public function setModelId(int $id): void
    {
        $this->stateChanges("modelId", $id);
    }

    public function setReason(?string $reason): void
    {
        $this->stateChanges("reason", $reason);
    }

    public function setTime(?string $time): void
    {
        $this->stateChanges("time", $time);
    }

    public function setPlaces($places): void
    {
        $codes = is_array($places) ? $places : explode(",", (string) $places);

        $codes = array_map(fn($code) => strtoupper(trim((string) $code)), $codes);
        $codes = array_values(array_filter($codes, fn($code) => $code !== ""));

        $this->stateChanges("places", implode(",", $codes));
    }

    public function isAppliesTo(?User $user): bool
    {
        $country = self::resolveCountry($user);
        if (is_null($country)) {
            return false;
        }

        return in_array(strtoupper($country), $this->getPlaces(), true);
    }

    public static function resolveCountry(?User $user): ?string
    {
        if (is_null($user)) {
            return null;
        }

        return self::resolveCountryByGeoIP() ?? self::resolveCountryByHeaders();
    }

    private static function resolveCountryByGeoIP(): ?string
    {
        if (!function_exists("geoip_country_code_by_name")) {
            return null;
        }

        # Chandler ships a legacy MaxMind GeoIP.dat database.
        $dbDir = OPENVK_ROOT . "/vendor/openvk/chandler/3rdparty/maxmind";
        if (is_dir($dbDir) && function_exists("geoip_setup_custom_directory")) {
            @\geoip_setup_custom_directory($dbDir . DIRECTORY_SEPARATOR);
        }

        $ip = defined("CONNECTING_IP") ? CONNECTING_IP : ($_SERVER["REMOTE_ADDR"] ?? null);
        if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $code = @\geoip_country_code_by_name($ip);
        if (is_string($code) && preg_match('/^[A-Za-z]{2}$/', $code)) {
            return strtoupper($code);
        }

        return null;
    }

    private static function resolveCountryByHeaders(): ?string
    {
        foreach (["HTTP_CF_IPCOUNTRY", "GEOIP_COUNTRY_CODE", "HTTP_X_COUNTRY_CODE", "HTTP_X_GEO_COUNTRY"] as $header) {
            $code = $_SERVER[$header] ?? null;
            if (is_string($code) && preg_match('/^[A-Za-z]{2}$/', $code)) {
                return strtoupper($code);
            }
        }

        return null;
    }

    public function save(?bool $log = false): void
    {
        if (is_null($this->getRecord())) {
            $this->stateChanges("created", time());
        }

        $this->stateChanges("edited", time());

        parent::save($log);
    }
}
