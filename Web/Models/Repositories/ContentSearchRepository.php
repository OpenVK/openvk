<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use Chandler\Database\DatabaseConnection;

class ContentSearchRepository
{
    private $ctx;
    private $builder;
    private $passedParams = [];
    private $tables       = [
        "albums",
        "photos",
        "posts",
    ];
    
    function __construct()
    {
        $this->ctx     = DatabaseConnection::i()->getContext();
        $this->builder = $this->ctx;
    }
    
    private function markParameterAsPassed(string $param): void
    {
        if(!in_array($param, $this->passedParams))
            $this->passedParams[] = $param;
    }
    
    function setContentType()
    {
        
    }
}
