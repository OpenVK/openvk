<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Repositories\Gifts;
use openvk\Web\Models\RowModel;

class GiftCategory extends RowModel
{
    protected $tableName = "gift_categories";
    
    private function getLocalization(string $language): object
    {
        return $this->getRecord()
            ->related("gift_categories_locales.category")
            ->where("language", $language);
    }
    
    private function createLocalizationIfNotExists(string $language): void
    {
        if(!is_null($this->getLocalization($language)->fetch()))
            return;
        
        DB::i()->getContext()->table("gift_categories_locales")->insert([
            "category"    => $this->getId(),
            "language"    => $language,
            "name"        => "Sample Text",
            "description" => "Sample Text",
        ]);
    }
    
    function getSlug(): string
    {
        return \Transliterator::createFromRules(
            ":: Any-Latin;"
            . ":: NFD;"
            . ":: [:Nonspacing Mark:] Remove;"
            . ":: NFC;"
            . ":: [:Punctuation:] Remove;"
            . ":: Lower();"
            . "[:Separator:] > '-'"
        )->transliterate($this->getName());
    }
    
    function getThumbnailURL(): string
    {
        $primeGift = iterator_to_array($this->getGifts(1, 1))[0];
        $serverUrl = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
        if(!$primeGift)
            return "$serverUrl/assets/packages/static/openvk/img/camera_200.png";
        
        return $primeGift->getImage(Gift::IMAGE_URL);
    }
    
    function getName(string $language = "_", bool $returnNull = false): ?string
    {
        $loc = $this->getLocalization($language)->fetch();
        if(!$loc) {
            if($returnNull)
                return NULL;
            
            return $language === "_" ? "Unlocalized" : $this->getName();
        }
        
        return $loc->name;
    }
    
    function getDescription(string $language = "_", bool $returnNull = false): ?string
    {
        $loc = $this->getLocalization($language)->fetch();
        if(!$loc) {
            if($returnNull)
                return NULL;
            
            return $language === "_" ? "Unlocalized" : $this->getDescription();
        }
        
        return $loc->description;
    }
    
    function getGifts(int $page = -1, ?int $perPage = NULL, &$count = nullptr): \Traversable
    {
        $gifts = $this->getRecord()->related("gift_relations.category");
        if($page !== -1) {
            $count = $gifts->count();
            $gifts = $gifts->page($page, $perPage ?? OPENVK_DEFAULT_PER_PAGE);
        }
        
        foreach($gifts as $rel)
            yield (new Gifts)->get($rel->gift);
    }
    
    function isMagical(): bool
    {
        return !is_null($this->getRecord()->autoquery);
    }
    
    function hasGift(Gift $gift): bool
    {
        $rels = $this->getRecord()->related("gift_relations.category");
        
        return $rels->where("gift", $gift->getId())->count() > 0;
    }
    
    function addGift(Gift $gift): void
    {
        if($this->hasGift($gift))
            return;
        
        DB::i()->getContext()->table("gift_relations")->insert([
            "category" => $this->getId(),
            "gift"     => $gift->getId(),
        ]);
    }
    
    function removeGift(Gift $gift): void
    {
        if(!$this->hasGift($gift))
            return;
        
        DB::i()->getContext()->table("gift_relations")->where([
            "category" => $this->getId(),
            "gift"     => $gift->getId(),
        ])->delete();
    }
    
    function setName(string $language, string $name): void
    {
        $this->createLocalizationIfNotExists($language);
        $this->getLocalization($language)->update([
            "name" => $name,
        ]);
    }
    
    function setDescription(string $language, string $description): void
    {
        $this->createLocalizationIfNotExists($language);
        $this->getLocalization($language)->update([
            "description" => $description,
        ]);
    }
    
    function setAutoQuery(?array $query = NULL): void
    {
        if(is_null($query)) {
            $this->stateChanges("autoquery", NULL);
            return;
        }
        
        $allowedColumns = ["price", "usages"];
        if(array_diff_key($query, array_flip($allowedColumns)))
            throw new \LogicException("Invalid query");
        
        $this->stateChanges("autoquery", serialize($query));
    }
}
