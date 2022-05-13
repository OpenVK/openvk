<?php declare(strict_types=1);
namespace openvk\Web\Util;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\{ClientException as GuzzleClientException, ConnectException as GuzzleConnectException};
use Chandler\Patterns\TSimpleSingleton;

class AirRaidsProvider
{
    private $data = [];

    private function fetchData(): bool
    {
        if(!empty($this->data))
            return true;

        $conf = (object) OPENVK_ROOT_CONF["openvk"]["credentials"]["airRaidAlerts"];
        if(!$conf->enable)
            return false;

        # Try to use cache
        if(file_exists(OPENVK_ROOT . "/tmp/air_raid_cache.json"))
            $cache = json_decode(file_get_contents(OPENVK_ROOT . "/tmp/air_raid_cache.json"));
        else
            $cache = NULL;

        if(!is_null($cache) && $cache->timestamp + 120 >= time()) {
            foreach($cache->states as $state)
                $this->data[] = [$state->name, $state->alert];
            
            return true;
        } else {
            try {
                $response = (new GuzzleClient)->request(
                    "GET",
                    "{$conf->instance}/api/states",
                    [
                        "headers" => [
                            "X-API-Key" => $conf->key
                        ]
                    ]
                );

                foreach(json_decode($response->getBody()->getContents())->states as $state)
                    $this->data[] = [$state->name, $state->alert];
    
                # Update cache
                file_put_contents(OPENVK_ROOT . "/tmp/air_raid_cache.json", json_encode([
                    "timestamp" => time(),
                    "states" => array_map(function($state) {
                        return [
                            "name"  => $state[0],
                            "alert" => $state[1]
                        ];
                    }, $this->data)
                ]));
    
                return true;
            } catch (GuzzleClientException | GuzzleConnectException $ex) {
                trigger_error("Could not fetch air raids: {$ex->getMessage()}", E_USER_WARNING);
                return false;
            }
        }
    }

    function getNameById(int $id): ?string
    {
        if($this->fetchData())
            return $this->data[$id][0];
        else
            return NULL;
    }

    function getStatusById(int $id): ?bool
    {
        if($this->fetchData())
            return $this->data[$id][1];
        else
            return NULL;
    }

    function getStates(): array
    {
        $this->fetchData();
        return array_map(function($state) {
            return $state[0];
        }, $this->data);
    }

    use TSimpleSingleton;
}
