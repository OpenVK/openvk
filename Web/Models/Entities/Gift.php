<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\User;
use Nette\Utils\{Image, ImageException};

class Gift extends RowModel
{
    const IMAGE_MAXSIZE = 131072;
    const IMAGE_BINARY  = 0;
    const IMAGE_BASE64  = 1;
    const IMAGE_URL     = 2;
    
    const PERIOD_IGNORE      = 0;
    const PERIOD_SET         = 1;
    const PERIOD_SET_IF_NONE = 2;
    
    protected $tableName = "gifts";
    
    function getName(): string
    {
        return $this->getRecord()->internal_name;
    }
    
    function getPrice(): int
    {
        return $this->getRecord()->price;
    }
    
    function getUsages(): int
    {
        return $this->getRecord()->usages;
    }
    
    function getUsagesBy(User $user, ?int $since = NULL): int
    {
        $sent = $this->getRecord()
                ->related("gift_user_relations.gift")
                ->where("sender", $user->getId())
                ->where("sent >= ?", $since ?? $this->getRecord()->limit_period ?? 0);
        
        return sizeof($sent);
    }
    
    function getUsagesLeft(User $user): float
    {
        if($this->getLimit() === INF)
            return INF;
        
        return max(0, $this->getLimit() - $this->getUsagesBy($user));
    }
    
    function getImage(int $type = 0): /* ?binary */ string
    {
        switch($type) {
            default:
            case static::IMAGE_BINARY:
                return $this->getRecord()->image ?? "";
                break;
            case static::IMAGE_BASE64:
                return "data:image/png;base64," . base64_encode($this->getRecord()->image ?? "");
                break;
            case static::IMAGE_URL:
                return "/gift" . $this->getId() . "_" . $this->getUpdateDate()->timestamp() . ".png";
                break;
        }
    }
    
    function getLimit(): float
    {
        $limit = $this->getRecord()->limit;
        
        return !$limit ? INF : (float) $limit;
    }
    
    function getLimitResetTime(): ?DateTime
    {
        return is_null($t = $this->getRecord()->limit_period) ? NULL : new DateTime($t);
    }
    
    function getUpdateDate(): DateTime
    {
        return new DateTime($this->getRecord()->updated);
    }
    
    function canUse(User $user): bool
    {
        return $this->getUsagesLeft($user) > 0;
    }
    
    function isFree(): bool
    {
        return $this->getPrice() === 0;
    }
    
    function used(): void
    {
        $this->stateChanges("usages", $this->getUsages() + 1);
        $this->save();
    }
    
    function setName(string $name): void
    {
        $this->stateChanges("internal_name", $name);
    }
    
    function setImage(string $file): bool
    {
        $imgBlob;
        try {
            $image = Image::fromFile($file);
            $image->resize(512, 512, Image::SHRINK_ONLY);
            
            $imgBlob = $image->toString(Image::PNG);
        } catch(ImageException $ex) {
            return false;
        }
        
        if(strlen($imgBlob) > (2**24 - 1)) {
            return false;
        } else {
            $this->stateChanges("updated", time());
            $this->stateChanges("image", $imgBlob);
        }
        
        return true;
    }
    
    function setLimit(?float $limit = NULL, int $periodBehaviour = 0): void
    {
        $limit ??= $this->getLimit();
        $limit   = $limit === INF ? NULL : (int) $limit;
        $this->stateChanges("limit", $limit);
        
        if(!$limit) {
            $this->stateChanges("limit_period", NULL);
            return;
        }
        
        switch($periodBehaviour) {
            default:
            case static::PERIOD_IGNORE:
                break;
            
            case static::PERIOD_SET:
                $this->stateChanges("limit_period", time());
                break;
            
            case static::PERIOD_SET_IF_NONE:
                if(is_null($this->getRecord()) || is_null($this->getRecord()->limit_period))
                    $this->stateChanges("limit_period", time());
                
                break;
        }
    }
    
    function delete(bool $softly = true): void
    {
        $this->getRecord()->related("gift_relations.gift")->delete();
        
        parent::delete($softly);
    }
}
