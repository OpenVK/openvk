<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;

final class Audio extends VKAPIRequestHandler
{
	function get(): object
	{
		$serverUrl = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
	
		return (object) [
			"count" => 1,
			"items" => [(object) [
				"id" 	   => 1,
				"owner_id" => 1,
				"artist"   => "В ОВК ПОКА НЕТ МУЗЫКИ",
				"title"    => "ЖДИТЕ :)))",
				"duration" => 22,
				"url"      => $serverUrl . "/assets/packages/static/openvk/audio/nomusic.mp3"
			]]
		];
	}
}
