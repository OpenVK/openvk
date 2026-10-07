<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Parsedown;
use Chandler\Session\Session;

final class DevelopersPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    protected $activationTolerant = true;
    protected $deactivationTolerant = true;
    protected $presenterName = "dev";

    private function getList(string $name): void {
        $name = ltrim($name, '/');

        $this->template->devMenu = [
            ["name" => "dsb_back",    "href" => "/id0",         "type" => "link"],
            ["name" => "dsb_intro",   "href" => "/dev",         "type" => "link"],
            ["name" => "dsb_main",    "href" => "/dev/main",    "type" => "link"],
            ["name" => "dsb_methods", "href" => "/dev/methods", "type" => "link"],
            ["name" => "dsb_models",  "href" => "/dev/models",  "type" => "link"],
        ];

        $isMethodsPage = ($name === 'methods' || str_starts_with($name, 'methods/'));
        $this->template->isMethodsPage = $isMethodsPage;

        if ($isMethodsPage) {
            $this->template->methodsGroups = [
                [
                    "id"    => "account",
                    "name"  => "dsb_m_account",
                    "links" => [
                        ["name" => "ban",                "href" => "/dev/methods/account/ban"],
                        ["name" => "unban",              "href" => "/dev/methods/account/unban"],
                        ["name" => "getBanned",          "href" => "/dev/methods/account/getBanned"],
                        ["name" => "getBalance",         "href" => "/dev/methods/account/getBalance"],
                        ["name" => "getCounters",        "href" => "/dev/methods/account/getCounters"],
                        ["name" => "getInfo",            "href" => "/dev/methods/account/getInfo"],
                        ["name" => "getProfileInfo",     "href" => "/dev/methods/account/getProfileInfo"],
                        ["name" => "getOvkSettings",     "href" => "/dev/methods/account/getOvkSettings"],
                        ["name" => "getViewerId",        "href" => "/dev/methods/account/getViewerId"],
                        ["name" => "getAppPermissions",  "href" => "/dev/methods/account/getAppPermissions"],
                        ["name" => "saveProfileInfo",    "href" => "/dev/methods/account/saveProfileInfo"],
                        ["name" => "saveInterestsInfo",  "href" => "/dev/methods/account/saveInterestsInfo"],
                        ["name" => "sendVotes",          "href" => "/dev/methods/account/sendVotes"],
                        ["name" => "setOnline",          "href" => "/dev/methods/account/setOnline"],
                        ["name" => "setOffline",         "href" => "/dev/methods/account/setOffline"],
                        ["name" => "registerDevice",     "href" => "/dev/methods/account/registerDevice"],
                        ["name" => "unregisterDevice",   "href" => "/dev/methods/account/unregisterDevice"],
                        ["name" => "setSilenceMode",     "href" => "/dev/methods/account/setSilenceMode"],
                        ["name" => "getPushSettings",    "href" => "/dev/methods/account/getPushSettings"],
                        ["name" => "get",                "href" => "/dev/methods/account/get"],
                        ["name" => "getMulti",           "href" => "/dev/methods/account/getMulti"],
                        ["name" => "getPrivacySettings", "href" => "/dev/methods/account/getPrivacySettings"],
                        ["name" => "getContactList",     "href" => "/dev/methods/account/getContactList"],
                        ["name" => "getHelpHints",       "href" => "/dev/methods/account/getHelpHints"],
                        ["name" => "getBadgesSettings",  "href" => "/dev/methods/account/getBadgesSettings"],
                        ["name" => "getToggles",         "href" => "/dev/methods/account/getToggles"],
                    ],
                ],
                [
                    "id"    => "audio",
                    "name"  => "dsb_m_audio",
                    "links" => [
                        ["name" => "audio.get",              "href" => "/dev/methods/audio/get"],
                        ["name" => "audio.search",           "href" => "/dev/methods/audio/search"],
                        ["name" => "audio.add",              "href" => "/dev/methods/audio/add"],
                    ],
                ],
                [
                    "id"    => "users",
                    "name"  => "dsb_m_users",
                    "links" => [
                        ["name" => "users.get",              "href" => "/dev/methods/users/get"],
                        ["name" => "users.search",           "href" => "/dev/methods/users/search"],
                    ],
                ],
            ];
        }
    }

    public function renderIndex(): void {
        $this->template->currentSection = "";
        $this->getList("");
    }

    public function renderDevelopersArticle(string $name): void
    {
        $name = ltrim($name, '/');

        if (empty($name) || $name === "elopers") {
            $this->redirect("/dev");
        }

        $lang = Session::i()->get("lang", "ru");
        $base = OPENVK_ROOT . "/data/knowledgebase/dev";
        if (file_exists("$base/$name.$lang.md")) {
            $file = "$base/$name.$lang.md";
        } elseif (file_exists("$base/$name.md")) {
            $file = "$base/$name.md";
        } else {
            $this->notFound();
        }

        $lines = file($file);
        if (!preg_match("%^OpenVK-KB-Heading: (.+)$%", $lines[0], $matches)) {
            $heading = "Article $name";
        } else {
            $heading = $matches[1];
            array_shift($lines);
        }

        $content = implode("", $lines);

        $parser = new Parsedown();
        $this->template->heading = $heading;
        $this->template->content = $parser->text($content);
        $this->template->currentSection = $name;

        $this->getList($name);
    }
}