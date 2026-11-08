<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Resolves the visitor's country from a local MaxMind-compatible database.
 *
 * The database is intentionally not bundled with OpenVK. Install GeoLite2 or
 * DB-IP Lite and point `openvk.preferences.geo.database` at the .mmdb file.
 */
final class GeoIp
{
    private static ?self $instance = null;

    private ?Reader $reader = null;
    private bool $readerTried = false;
    private bool $resolved = false;
    private ?string $country = null;

    public static function i(): self
    {
        return self::$instance ??= new self();
    }

    public function country(): ?string
    {
        if ($this->resolved) {
            return $this->country;
        }

        $this->resolved = true;

        if (!(bool) ($this->config()["enabled"] ?? false)) {
            return $this->country = null;
        }

        $headerCountry = $this->headerCountry();
        if ($headerCountry !== null) {
            return $this->country = $headerCountry;
        }

        $ip = $this->clientIp();
        if ($ip === null || !$this->isPublicIp($ip)) {
            return $this->country = null;
        }

        $reader = $this->reader();
        if ($reader === null) {
            return $this->country = null;
        }

        try {
            return $this->country = $reader->country($ip)->country->isoCode ?: null;
        } catch (AddressNotFoundException | InvalidDatabaseException) {
            return $this->country = null;
        } catch (\Throwable) {
            return $this->country = null;
        }
    }

    public function clientIp(): ?string
    {
        $remote = $_SERVER["REMOTE_ADDR"] ?? null;
        if ($remote === null || !filter_var($remote, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (!$this->isTrustedProxy($remote)) {
            return $remote;
        }

        $forwarded = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? "";
        if ($forwarded !== "") {
            $parts = array_reverse(array_map("trim", explode(",", $forwarded)));
            foreach ($parts as $part) {
                if (filter_var($part, FILTER_VALIDATE_IP) && !$this->isTrustedProxy($part)) {
                    return $part;
                }
            }
        }

        $realIp = $_SERVER["HTTP_X_REAL_IP"] ?? "";
        if ($realIp !== "" && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        return $remote;
    }

    private function headerCountry(): ?string
    {
        $header = $this->config()["headerCountry"] ?? null;
        if (!$header || !$this->isTrustedProxy($_SERVER["REMOTE_ADDR"] ?? "")) {
            return null;
        }

        $serverKey = "HTTP_" . strtoupper(str_replace("-", "_", (string) $header));
        $value = strtoupper(trim((string) ($_SERVER[$serverKey] ?? "")));

        return preg_match("/^[A-Z]{2}$/", $value) === 1 ? $value : null;
    }

    private function reader(): ?Reader
    {
        if ($this->readerTried) {
            return $this->reader;
        }

        $this->readerTried = true;
        $database = (string) ($this->config()["database"] ?? "");
        if ($database === "" || !is_file($database) || !is_readable($database)) {
            return null;
        }

        try {
            return $this->reader = new Reader($database);
        } catch (\Throwable) {
            return null;
        }
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function isTrustedProxy(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach ($this->trustedProxies() as $cidr) {
            if ($this->inCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function trustedProxies(): array
    {
        $value = $this->config()["trustedProxies"] ?? [];
        if (is_string($value)) {
            $value = [$value];
        }

        return is_array($value) ? array_values(array_filter($value, "is_string")) : [];
    }

    private function inCidr(string $ip, string $cidr): bool
    {
        $parts = explode("/", trim($cidr), 2);
        $subnet = $parts[0] ?? "";
        $mask = isset($parts[1]) ? (int) $parts[1] : null;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;
        if ($mask === null) {
            $mask = $maxBits;
        }
        if ($mask < 0 || $mask > $maxBits) {
            return false;
        }

        $wholeBytes = intdiv($mask, 8);
        $remainingBits = $mask % 8;

        if ($wholeBytes > 0 && substr($ipBin, 0, $wholeBytes) !== substr($subnetBin, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits > 0) {
            $byteMask = (0xFF << (8 - $remainingBits)) & 0xFF;

            return (ord($ipBin[$wholeBytes]) & $byteMask) === (ord($subnetBin[$wholeBytes]) & $byteMask);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $config = OPENVK_ROOT_CONF["openvk"]["preferences"]["geo"] ?? [];

        return is_array($config) ? $config : [];
    }
}
