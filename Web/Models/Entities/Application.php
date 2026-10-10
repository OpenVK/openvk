<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use Chandler\Database\DatabaseConnection;
use Nette\Utils\Image;
use Nette\Utils\UnknownImageFileException;
use openvk\Web\Models\Repositories\Notes;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class Application extends RowModel
{
    protected $tableName = "apps";

    public const PERMS = [
        "notify",
        "friends",
        "photos",
        "audio",
        "video",
        "stories",
        "pages",
        "status",
        "notes",
        "messages",
        "wall",
        "ads",
        "docs",
        "groups",
        "notifications",
        "stats",
        "email",
        "market",
    ];

    private function getAvatarsDir(): string
    {
        $uploadSettings = OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"];
        if ($uploadSettings["mode"] === "server" && $uploadSettings["server"]["kind"] === "cdn") {
            return $uploadSettings["server"]["directory"];
        } else {
            return OPENVK_ROOT . "/storage/";
        }
    }

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getOwner(): User
    {
        return (new Users())->get($this->getRecord()->owner);
    }

    public function getName(): string
    {
        return $this->getRecord()->name;
    }

    public function getDescription(): string
    {
        return $this->getRecord()->description;
    }

    public function getAvatarUrl(): string
    {
        $serverUrl = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        if (is_null($this->getRecord()->avatar_hash)) {
            return "$serverUrl/assets/packages/static/openvk/img/camera_200.png";
        }

        $hash = $this->getRecord()->avatar_hash;
        switch (OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["mode"]) {
            default:
            case "default":
            case "basic":
                return "$serverUrl/blob_" . substr($hash, 0, 2) . "/$hash" . "_app_avatar.png";
            case "accelerated":
                return "$serverUrl/openvk-datastore/$hash" . "_app_avatar.png";
            case "server":
                $settings = (object) OPENVK_ROOT_CONF["openvk"]["preferences"]["uploads"]["server"];
                return (
                    ($settings->protocol ?? ovk_scheme()) .
                    "://" . $settings->host .
                    $settings->path .
                    substr($hash, 0, 2) . "/$hash" . "_app_avatar.png"
                );
        }
    }

    public function getNote(): ?Note
    {
        if (!$this->getRecord()->news) {
            return null;
        }

        return (new Notes())->get($this->getRecord()->news);
    }

    public function getNoteLink(): string
    {
        $note = $this->getNote();
        if (!$note) {
            return "";
        }

        return ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/note" . $note->getPrettyId();
    }

    public function getBalance(): float
    {
        return $this->getRecord()->coins;
    }

    public function getURL(): string
    {
        return $this->getRecord()->address;
    }

    public function getOrigin(): string
    {
        $parsed = parse_url($this->getURL());
        $scheme = strtolower($parsed["scheme"] ?? "https");
        $origin = $scheme . "://" . strtolower($parsed["host"] ?? "127.0.0.1");

        # must match browser's event.origin, which omits default ports
        $port = $parsed["port"] ?? null;
        if (!is_null($port) && $port !== ($scheme === "http" ? 80 : 443)) {
            $origin .= ":$port";
        }

        return $origin;
    }

    public function isStrict(): bool
    {
        return (bool) $this->getRecord()->strict;
    }

    public function getSecret(): string
    {
        $secret = $this->getRecord()->secret;
        if (is_null($secret)) {
            # conditional update, so concurrent requests can't end up with different secrets
            $cx = DatabaseConnection::i()->getContext();
            $cx->table("apps")->where(["id" => $this->getId(), "secret" => null])->update(["secret" => $this->generateSecret()]);
            $secret = $cx->table("apps")->get($this->getId())->secret;
        }

        return $secret;
    }

    public function regenerateSecret(): void
    {
        $this->stateChanges("secret", $this->generateSecret());
        $this->save();
    }

    private function generateSecret(): string
    {
        return $this->base64url(random_bytes(32));
    }

    private function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), "+/", "-_"), "=");
    }

    /**
     * Adds ovk_sign to the ovk_* params. Keys and values must only contain [A-Za-z0-9._-].
     */
    public function signParams(array $params): array
    {
        ksort($params, SORT_STRING);
        $canonical = implode("&", array_map(fn($k, $v) => "$k=$v", array_keys($params), $params));
        $sign      = hash_hmac("sha256", $canonical, $this->getSecret(), true);

        return $params + ["ovk_sign" => $this->base64url($sign)];
    }

    public function getLaunchURL(User $user): string
    {
        $params = $this->signParams([
            "ovk_app_id"    => (string) $this->getId(),
            "ovk_user_id"   => (string) $user->getId(),
            "ovk_ts"        => (string) time(),
            "ovk_launch_id" => bin2hex(random_bytes(16)),
            "ovk_type"      => "launch",
        ]);

        [$url, $fragment] = array_pad(explode("#", $this->getURL(), 2), 2, null);
        $url .= (str_contains($url, "?") ? "&" : "?") . http_build_query($params);

        return is_null($fragment) ? $url : "$url#$fragment";
    }

    public function getUsersCount(): int
    {
        return (int) $this->getRecord()->installs;
    }

    public function getInstallationEntry(User $user): ?array
    {
        $cx    = DatabaseConnection::i()->getContext();
        $entry = $cx->table("app_users")->where([
            "app"  => $this->getId(),
            "user" => $user->getId(),
        ])->fetch();

        if (!$entry) {
            return null;
        }

        return $entry->toArray();
    }

    public function getPermissions(User $user): array
    {
        $permMask    = 0;
        $installInfo = $this->getInstallationEntry($user);
        if (!$installInfo) {
            $this->install($user);
        } else {
            $permMask = $installInfo["access"];
        }

        $res = [];
        for ($i = 0; $i < sizeof(self::PERMS); $i++) {
            $checkVal = 1 << $i;
            if (($permMask & $checkVal) > 0) {
                $res[] = self::PERMS[$i];
            }
        }

        return $res;
    }

    public function isInstalledBy(User $user): bool
    {
        return !is_null($this->getInstallationEntry($user));
    }

    public function setNoteLink(?string $link): bool
    {
        if (!$link) {
            $this->stateChanges("news", null);

            return true;
        }

        preg_match("%note([0-9]+)_([0-9]+)$%", $link, $matches);
        if (sizeof($matches) != 3) {
            return false;
        }

        $owner = is_null($this->getRecord()) ? $this->changes["owner"] : $this->getRecord()->owner;
        [, $ownerId, $vid] = $matches;
        if ($ownerId != $owner) {
            return false;
        }

        $note = (new Notes())->getNoteById((int) $ownerId, (int) $vid);
        if (!$note) {
            return false;
        }

        $this->stateChanges("news", $note->getId());

        return true;
    }

    public function setAvatar(array $file): int
    {
        if ($file["error"] !== UPLOAD_ERR_OK) {
            return -1;
        }

        try {
            $image = Image::fromFile($file["tmp_name"]);
        } catch (UnknownImageFileException $e) {
            return -2;
        }

        $hash = hash_file("adler32", $file["tmp_name"]);
        if (!is_dir($this->getAvatarsDir() . substr($hash, 0, 2))) {
            if (!mkdir($this->getAvatarsDir() . substr($hash, 0, 2))) {
                return -3;
            }
        }

        $image->resize(140, 140, Image::STRETCH);
        $image->save($this->getAvatarsDir() . substr($hash, 0, 2) . "/$hash" . "_app_avatar.png");

        $this->stateChanges("avatar_hash", $hash);

        return 0;
    }

    public function setPermission(User $user, string $perm, bool $enabled): bool
    {
        $permMask    = 0;
        $installInfo = $this->getInstallationEntry($user);
        if (!$installInfo) {
            $this->install($user);
        } else {
            $permMask = $installInfo["access"];
        }

        $index = array_search($perm, self::PERMS);
        if ($index === false) {
            return false;
        }

        $permVal  = 1 << $index;
        $permMask = $enabled ? ($permMask | $permVal) : ($permMask ^ $permVal);

        $cx = DatabaseConnection::i()->getContext();
        $cx->table("app_users")->where([
            "app"  => $this->getId(),
            "user" => $user->getId(),
        ])->update([
            "access" => $permMask,
        ]);

        return true;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->getRecord()->enabled;
    }

    public function enable(): void
    {
        $this->stateChanges("enabled", 1);
        $this->save();
    }

    public function disable(): void
    {
        $this->stateChanges("enabled", 0);
        $this->save();
    }

    public function install(User $user): void
    {
        $this->changeInstalls($user, true);
    }

    public function uninstall(User $user): void
    {
        $this->changeInstalls($user, false);
    }

    /**
     * Adds or removes the user's app_users row and keeps apps.installs in step, in one transaction.
     * The counter only changes if the row really did.
     */
    private function changeInstalls(User $user, bool $install): void
    {
        $db = DatabaseConnection::i()->getContext();
        $db->beginTransaction();
        try {
            if ($install) {
                # IGNORE: opening the app in two tabs at once installs it once
                $changed = $db->query("INSERT IGNORE INTO app_users (app, user) VALUES (?, ?)", $this->getId(), $user->getId())->getRowCount();
                $counter = "UPDATE apps SET installs = installs + 1 WHERE id = ?";
            } else {
                $changed = $db->query("DELETE FROM app_users WHERE app = ? AND user = ?", $this->getId(), $user->getId())->getRowCount();
                $counter = "UPDATE apps SET installs = GREATEST(installs, 1) - 1 WHERE id = ?";
            }

            if ($changed > 0) {
                $db->query($counter, $this->getId());
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Moves the app's balance, minus the tax, to its owner. Returns the amount taken from the app.
     * The balance is locked and emptied in one transaction, so a payment arriving meanwhile isn't lost.
     */
    public function withdrawCoins(): float
    {
        $db = DatabaseConnection::i()->getContext();
        $db->beginTransaction();
        try {
            # the user's balance before the app's, in the same order as payments: an owner paying their own app
            # while withdrawing must not deadlock
            $db->query("SELECT coins FROM profiles WHERE id = ? FOR UPDATE", $this->getOwner()->getId());
            $balance = (float) $db->query("SELECT coins FROM apps WHERE id = ? FOR UPDATE", $this->getId())->fetchField();
            $tax     = ($balance / 100) * OPENVK_ROOT_CONF["openvk"]["preferences"]["apps"]["withdrawTax"];

            $db->query("UPDATE apps SET coins = 0 WHERE id = ?", $this->getId());
            $db->query("UPDATE profiles SET coins = coins + ? WHERE id = ?", $balance - $tax, $this->getOwner()->getId());
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return $balance;
    }

    public function delete(bool $softly = true): void
    {
        $cx = DatabaseConnection::i()->getContext();
        $app_users = $cx->table("app_users")->where("app", $this->getId());

        if ($softly) {
            $app_users->update(["deleted" => 1]);
        } else {
            $app_users->delete();
        }

        parent::delete($softly);
    }

    public function getPublicationTime(): string
    {
        return tr("recently");
    }
}
