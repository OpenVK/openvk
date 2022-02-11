<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Repositories\Users;

final class ActivityPubPresenter extends OpenVKPresenter
{    
    function renderWellKnown(): void
    {
        if ($_SERVER['REQUEST_URI'] == "/.well-known/host-meta") {
            $data = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">'
            . '<Link rel="lrdd" template="' . ovk_scheme(true) . $_SERVER["SERVER_NAME"] . '/.well-known/webfinger?resource={uri}"/>'
            . '</XRD>';

            header("Content-Type: application/xrd+xml; charset=utf-8");
            exit($data);
        } else if ($_SERVER['REQUEST_URI'] == "/.well-known/nodeinfo") {
            $response->links = array(array(
                "href" => ovk_scheme(true) . $_SERVER["SERVER_NAME"] . '/nodeinfo/2.0',
                'rel' => "http://nodeinfo.diaspora.software/ns/schema/2.0"
            ));
            
            $this->returnJson((array) $response);
        } else if ($this->startsWith($_SERVER["REQUEST_URI"], '/.well-known/webfinger') && $this->startsWith($this->requestParam("resource"), 'acct:')) {
            $username = array();
            $subject = substr($this->requestParam("resource"), 5, strlen($this->requestParam("resource")));
            preg_match('/([a-zA-Z0-9-_]+)@([a-zA-Z0-9-_\.]+)/', $subject, $username);
            $username = $username[1];

            $user = (new Users)->getByShortURL($username, true);

            if($user !== null) {
                $response->subject = $this->requestParam("resource");
                
                $response->links[] = array(
                    'rel' => 'self',
                    'href' => ovk_scheme(true) . $_SERVER["SERVER_NAME"] . '/id' . $user->getId(),
                    'type' => 'application/activity+json'
                );

                header("Access-Control-Allow-Origin: *");
                $this->returnJson((array) $response);
            } else {
                header("HTTP/2 404 Not Found");
                exit();
            };
        } else {
            header("HTTP/2 404 Not Found");
            exit();
        }
    }

    function renderNodeinfo()
    {
        $stats = (new Users)->getStatistics();

        $response->version = '2.0';
        $response->protocols = array('activitypub');
        $response->openRegistrations = OPENVK_ROOT_CONF['openvk']['preferences']['registration']['enable'];
        $response->software = array('name' => 'openvk',
                                    'version' => OPENVK_VERSION);
        $response->usage    = array('localPosts' => $stats->posts,
                                    'localComments' => $stats->comments,
                                    'users' => array('total' => $stats->all));
        $response->metadata = (object)array();

        $this->returnJson((array) $response);
    }

    private function startsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }
}
