<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

final class Ovk extends VKAPIRequestHandler
{
    function version(): string
    {
        return OPENVK_VERSION;
    }
    
    function test(): object
    {
        return (object) [
            "authorized" => $this->userAuthorized(),
            "auth_with"  => $_GET["auth_mechanism"] ?? "access_token",
            "version"    => VKAPI_DECL_VER,
        ];
    }
    
    function chickenWings(): string
    {
        return "крылышки";
    }
}
