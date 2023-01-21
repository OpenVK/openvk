<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\VKAPI\Exceptions\APIErrorException;
use openvk\Web\Models\Entities\User;

abstract class VKAPIRequestHandler
{
    protected $user;
    protected $platform;
    
    function __construct(?User $user = NULL, ?string $platform = NULL)
    {
        $this->user     = $user;
        $this->platform = $platform;
    }
    
    protected function fail(int $code, string $message): void
    {
        throw new APIErrorException($message, $code);
    }
    
    protected function getUser(): ?User
    {
        return $this->user;
    }
    
    protected function getPlatform(): ?string
    {
        return $this->platform;
    }
    
    protected function userAuthorized(): bool
    {
        return !is_null($this->getUser());
    }
    
    protected function requireUser(): void
    {
        if(!$this->userAuthorized())
            $this->fail(5, "User authorization failed: no access_token passed.");
    }
}
