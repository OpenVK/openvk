<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use Chandler\Security\Authenticator;
use Chandler\Database\DatabaseConnection as DB;
use openvk\VKAPI\Exceptions\APIErrorException;
use openvk\Web\Models\Entities\{User, APIToken};
use openvk\Web\Models\Repositories\{Users, APITokens};

final class VKAPIPresenter extends OpenVKPresenter
{
    private function logRequest(string $object, string $method): void
    {
        $date   = date(DATE_COOKIE);
        $params = json_encode($_REQUEST);
        $log    = "[$date] $object.$method called with $params\r\n";
        file_put_contents(OPENVK_ROOT . "/VKAPI/debug.log", $log, FILE_APPEND | LOCK_EX);
    }
    
    private function fail(int $code, string $message, string $object, string $method): void
    {
        header("HTTP/1.1 400 Bad API Call");
        header("Content-Type: application/json");
        
        $payload = [
            "error_code"     => $code,
            "error_msg"      => $message,
            "request_params" => [
                [
                    "key"   => "method",
                    "value" => "$object.$method",
                ],
                [
                    "key"   => "oauth",
                    "value" => 1,
                ],
            ],
        ];
        
        foreach($_GET as $key => $value)
            array_unshift($payload["request_params"], [ "key" => $key, "value" => $value ]);
        
        exit(json_encode($payload));
    }
    
    private function badMethod(string $object, string $method): void
    {
        $this->fail(3, "Unknown method passed.", $object, $method);
    }
    
    private function badMethodCall(string $object, string $method, string $param): void
    {
        $this->fail(100, "Required parameter '$param' missing.", $object, $method);
    }
    
    function onStartup(): void
    {
        parent::onStartup();
        
        # idk, but in case we will ever support non-standard HTTP credential authflow
        $origin = "*";
        if(isset($_SERVER["HTTP_REFERER"])) {
            $refOrigin = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_SCHEME) . "://" . parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
            if($refOrigin !== false)
                $origin = $refOrigin;
        }
        
        if(!is_null($this->queryParam("requestPort")))
            $origin .= ":" . ((int) $this->queryParam("requestPort"));
        
        header("Access-Control-Allow-Origin: $origin");
        
        if($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            header("Access-Control-Allow-Methods: POST, PUT, DELETE");
            header("Access-Control-Allow-Headers: " . $_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]);
            header("Access-Control-Max-Age: -1");
            exit; # Terminate request processing as this is definitely a CORS preflight request.
        }
    }
    
    function renderRoute(string $object, string $method): void
    {
        $authMechanism = $this->queryParam("auth_mechanism") ?? "token";
        if($authMechanism === "roaming") {
            if(!$this->user->identity)
                $this->fail(5, "User authorization failed: roaming mechanism is selected, but user is not logged in.", $object, $method);
            else
                $identity = $this->user->identity;
        } else {
            if(is_null($this->requestParam("access_token"))) {
                $identity = NULL;
            } else {
                $token = (new APITokens)->getByCode($this->requestParam("access_token"));
                if(!$token)
                    $identity = NULL;
                else
                    $identity = $token->getUser();
            }
        }
        
        $object       = ucfirst(strtolower($object));
        $handlerClass = "openvk\\VKAPI\\Handlers\\$object";
        if(!class_exists($handlerClass))
            $this->badMethod($object, $method);
        
        $handler = new $handlerClass($identity);
        if(!is_callable([$handler, $method]))
            $this->badMethod($object, $method);
        
        $route  = new \ReflectionMethod($handler, $method);
        $params = [];
        foreach($route->getParameters() as $parameter) {
            $val = $this->requestParam($parameter->getName());
            if(is_null($val)) {
                if($parameter->allowsNull())
                    $val = NULL;
                else if($parameter->isDefaultValueAvailable())
                    $val = $parameter->getDefaultValue();
                else if($parameter->isOptional())
                    $val = NULL;
                else
                    $this->badMethodCall($object, $method, $parameter->getName());
            }
            
            settype($val, $parameter->getType()->getName());
            $params[] = $val;
        }
        
        define("VKAPI_DECL_VER", $this->requestParam("v") ?? "4.100", false);
        
        try {
            $res = $handler->{$method}(...$params);
        } catch(APIErrorException $ex) {
            $this->fail($ex->getCode(), $ex->getMessage(), $object, $method);
        }
        
        $result = json_encode([
            "response" => $res,
        ]);
        
        $size = strlen($result);
        header("Content-Type: application/json");
        header("Content-Length: $size");
        exit($result);
    }
    
    function renderTokenLogin(): void
    {
        if($this->requestParam("grant_type") !== "password")
            $this->fail(7, "Invalid grant type", "internal", "acquireToken");
        else if(is_null($this->requestParam("username")) || is_null($this->requestParam("password")))
            $this->fail(100, "Password and username not passed", "internal", "acquireToken");
        
        $chUser = DB::i()->getContext()->table("ChandlerUsers")->where("login", $this->requestParam("username"))->fetch();
        if(!$chUser)
            $this->fail(28, "Invalid username or password", "internal", "acquireToken");
        
        $auth = Authenticator::i();
        if(!$auth->verifyCredentials($chUser->id, $this->requestParam("password")))
            $this->fail(28, "Invalid username or password", "internal", "acquireToken");
        
        $uId  = $chUser->related("profiles.user")->fetch()->id;
        $user = (new Users)->get($uId);
        
        $token = new APIToken;
        $token->setUser($user);
        $token->save();
        
        $payload = json_encode([
            "access_token" => $token->getFormattedToken(),
            "expires_in"   => 0,
            "user_id"      => $uId,
        ]);
        
        $size = strlen($payload);
        header("Content-Type: application/json");
        header("Content-Length: $size");
        exit($payload);
    }
}
