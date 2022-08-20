<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;

class Application extends RowModel
{
    protected $tableName = "apps";
    
    const PERMS = [
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
    
    function getId(): int
    {
        return $this->getRecord()->id;
    }
    
    function getOwner(): User
    {
        return (new Users)->get($this->getRecord()->owner);
    }
    
    function getName(): string
    {
        return $this->getRecord()->name;
    }
    
    function getDescription(): string
    {
        return $this->getRecord()->description;
    }
    
    function getAvatarUrl(): string
    {
        return "";
    }
    
    function getBalance(): float
    {
        return $this->getRecord()->coins;
    }
    
    function getURL(): string
    {
        return $this->getRecord()->address;
    }
    
    function getOrigin(): string
    {
        $parsed = parse_url($this->getURL());
        
        return (
            ($parsed["scheme"] ?? "https") . "://"
            . ($parsed["host"] ?? "127.0.0.1") . ":"
            . ($parsed["port"] ?? "443")
        );
    }
    
    function getInstallationEntry(User $user): ?array
    {
        $cx    = DatabaseConnection::i()->getContext();
        $entry = $cx->table("app_users")->where([
            "app"  => $this->getId(),
            "user" => $user->getId(),
        ])->fetch();
        
        if(!$entry)
            return NULL;
        
        return $entry->toArray();
    }
    
    function getPermissions(User $user): array
    {
        $permMask    = 0;
        $installInfo = $this->getInstallationEntry($user);
        if(!$installInfo)
            $this->install($user);
        else
            $permMask = $installInfo["access"];
        
        $res = [];
        for($i = 0; $i < sizeof(self::PERMS); $i++) {
            $checkVal = 1 << $i;
            if(($permMask & $checkVal) > 0)
                $res[] = self::PERMS[$i];
        }
        
        return $res;
    }
    
    function isInstalledBy(User $user): bool
    {
        return !is_null($this->getInstallationEntry($user));
    }
    
    function setPermission(User $user, string $perm, bool $enabled): bool
    {
        $permMask    = 0;
        $installInfo = $this->getInstallationEntry($user);
        if(!$installInfo)
            $this->install($user);
        else
            $permMask = $installInfo["access"];
        
        $index = array_search($perm, self::PERMS);
        if($index === false)
            return false;
        
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
    
    function isEnabled(): bool
    {
        return (bool) $this->getRecord()->enabled;
    }
    
    function enable(): void
    {
        $this->stateChanges("enabled", 1);
        $this->save();
    }
    
    function disable(): void
    {
        $this->stateChanges("enabled", 0);
        $this->save();
    }
    
    function install(User $user): void
    {
        if(!$this->getInstallationEntry($user)) {
            $cx = DatabaseConnection::i()->getContext();
            $cx->table("app_users")->insert([
                "app"  => $this->getId(),
                "user" => $user->getId(),
            ]);
        }
    }
    
    function uninstall(User $user): void
    {
        $cx = DatabaseConnection::i()->getContext();
        $cx->table("app_users")->where([
            "app"  => $this->getId(),
            "user" => $user->getId(),
        ])->delete();
    }
    
    function addCoins(float $coins): float
    {
        $res = $this->getBalance() + $coins;
        $this->stateChanges("coins", $res);
        $this->save();
        
        return $res;
    }
    
    function withdrawCoins(): void
    {
        $owner = $this->getOwner();
        $owner->setCoins($owner->getCoins() + $this->getBalance());
        $this->setCoins(0.0);
        $this->save();
        $owner->save();
    }
    
    function delete(bool $softly = true): void
    {
        if($softly)
            throw new \UnexpectedValueException("Can't delete apps softly.");
    
        $cx = DatabaseConnection::i()->getContext();
        $cx->table("app_users")->where("app", $this->getId())->delete();
        
        parent::delete(false);
    }
}