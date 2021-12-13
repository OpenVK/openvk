<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities\WikiPage;
use openvk\Web\Models\Repositories\WikiPages;
use openvk\Web\Models\Entities\Postable;

class WikiPage extends Postable {
    protected $tableName = "wikipages";
    
    function getTitle(): string
    {
        return $this->getRecord()->title;
    }
    
    function getSource(): string
    {
        return $this->getRecord()->source;
    }
    
    function getHitCounter(): int
    {
        return $this->getRecord()->hits;
    }
    
    function getText(array $ctx = []): string
    {
        $counter = 0;
        $parser  = new Parser($this, new WikiPages, $counter, ["firstInclusion" => "yes"], array_merge([
            "time" => time(),
        ], $ctx));
        
        return $parser->asHTML();
    }
    
    function view(): void
    {
        $this->stateChanges("hits", $this->getRecord()->hits + 1);
        $this->save();
    }
}
