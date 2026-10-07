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
            ["name" => "dsb_back",       "href" => "/id0",            "type" => "link"],
            ["name" => "dsb_intro",      "href" => "/dev",            "type" => "link"],
            ["name" => "dsb_main",       "href" => "/dev/main",       "type" => "link"],
            ["name" => "dsb_methods",    "href" => "/dev/methods",    "type" => "link"],
            ["name" => "dsb_models",     "href" => "/dev/models",     "type" => "link"],
            ["name" => "dsb_standalone", "href" => "/dev/standalone", "type" => "link"],
            ["name" => "dsb_sites",      "href" => "/dev/sites",      "type" => "link"],
            ["name" => "dsb_native",     "href" => "/dev/native",     "type" => "link"],
            ["name" => "dsb_bugs",       "href" => "/dev/bugs",       "type" => "link"],
        ];

        $isMethodsPage = ($name === 'methods' || str_starts_with($name, 'methods/'));
        $this->template->isMethodsPage = $isMethodsPage;

        $isModelsPage = ($name === 'models' || str_starts_with($name, 'models/'));
        $this->template->isModelsPage = $isModelsPage;

        if ($isModelsPage) {
            $this->template->modelsList = [
                ["name" => "user",         "href" => "/dev/models/user"],
                ["name" => "group",        "href" => "/dev/models/group"],
                ["name" => "chat",         "href" => "/dev/models/chat"],
                ["name" => "conversation", "href" => "/dev/models/conversation"],
                ["name" => "message",      "href" => "/dev/models/message"],
                ["name" => "post",         "href" => "/dev/models/post"],
                ["name" => "comment",      "href" => "/dev/models/comment"],
                ["name" => "photo",        "href" => "/dev/models/photo"],
                ["name" => "audio",        "href" => "/dev/models/audio"],
                ["name" => "video",        "href" => "/dev/models/video"],
                ["name" => "doc",          "href" => "/dev/models/doc"],
                ["name" => "topic",        "href" => "/dev/models/topic"],
                ["name" => "poll",         "href" => "/dev/models/poll"],
                ["name" => "note",         "href" => "/dev/models/note"],
                ["name" => "album",        "href" => "/dev/models/album"],
                ["name" => "playlist",     "href" => "/dev/models/playlist"],
                ["name" => "sticker",      "href" => "/dev/models/sticker"],
                ["name" => "gift",         "href" => "/dev/models/gift"],
            ];
        }

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
                    "id"    => "activity",
                    "name"  => "dsb_m_activity",
                    "links" => [
                        ["name" => "online",                     "href" => "/dev/methods/activity/online"],
                    ],
                ],
                [
                    "id"    => "apps",
                    "name"  => "dsb_m_apps",
                    "links" => [
                        ["name" => "getMiniAppsCatalog",         "href" => "/dev/methods/apps/getMiniAppsCatalog"],
                        ["name" => "getMiniAppsCatalogSearch",   "href" => "/dev/methods/apps/getMiniAppsCatalogSearch"],
                    ],
                ],
                [
                    "id"    => "audio",
                    "name"  => "dsb_m_audio",
                    "links" => [
                        ["name" => "get",                "href" => "/dev/methods/audio/get"],
                        ["name" => "getById",            "href" => "/dev/methods/audio/getById"],
                        ["name" => "search",             "href" => "/dev/methods/audio/search"],
                        ["name" => "getCount",           "href" => "/dev/methods/audio/getCount"],
                        ["name" => "getPopular",         "href" => "/dev/methods/audio/getPopular"],
                        ["name" => "getFeed",            "href" => "/dev/methods/audio/getFeed"],
                        ["name" => "getLyrics",          "href" => "/dev/methods/audio/getLyrics"],
                        ["name" => "add",                "href" => "/dev/methods/audio/add"],
                        ["name" => "delete",             "href" => "/dev/methods/audio/delete"],
                        ["name" => "restore",            "href" => "/dev/methods/audio/restore"],
                        ["name" => "edit",               "href" => "/dev/methods/audio/edit"],
                        ["name" => "setBroadcast",       "href" => "/dev/methods/audio/setBroadcast"],
                        ["name" => "getBroadcastList",   "href" => "/dev/methods/audio/getBroadcastList"],
                        ["name" => "beacon",             "href" => "/dev/methods/audio/beacon"],
                        ["name" => "getAlbums",          "href" => "/dev/methods/audio/getAlbums"],
                        ["name" => "getPlaylistById",    "href" => "/dev/methods/audio/getPlaylistById"],
                        ["name" => "searchAlbums",       "href" => "/dev/methods/audio/searchAlbums"],
                        ["name" => "addAlbum",           "href" => "/dev/methods/audio/addAlbum"],
                        ["name" => "editAlbum",          "href" => "/dev/methods/audio/editAlbum"],
                        ["name" => "deleteAlbum",        "href" => "/dev/methods/audio/deleteAlbum"],
                        ["name" => "moveToAlbum",        "href" => "/dev/methods/audio/moveToAlbum"],
                        ["name" => "removeFromAlbum",    "href" => "/dev/methods/audio/removeFromAlbum"],
                        ["name" => "bookmarkAlbum",      "href" => "/dev/methods/audio/bookmarkAlbum"],
                        ["name" => "unBookmarkAlbum",    "href" => "/dev/methods/audio/unBookmarkAlbum"],
                        ["name" => "getRecommendations", "href" => "/dev/methods/audio/getRecommendations"],
                        ["name" => "isLagtrain",         "href" => "/dev/methods/audio/isLagtrain"],
                        ["name" => "subscribeToQueue",   "href" => "/dev/methods/audio/subscribeToQueue"],
                    ],
                ],
                [
                    "id"    => "board",
                    "name"  => "dsb_m_board",
                    "links" => [
                        ["name" => "getTopics",       "href" => "/dev/methods/board/getTopics"],
                        ["name" => "getComments",     "href" => "/dev/methods/board/getComments"],
                        ["name" => "addTopic",        "href" => "/dev/methods/board/addTopic"],
                        ["name" => "addChatTopic",    "href" => "/dev/methods/board/addChatTopic"],
                        ["name" => "createComment",   "href" => "/dev/methods/board/createComment"],
                        ["name" => "editTopic",       "href" => "/dev/methods/board/editTopic"],
                        ["name" => "closeTopic",      "href" => "/dev/methods/board/closeTopic"],
                        ["name" => "openTopic",       "href" => "/dev/methods/board/openTopic"],
                        ["name" => "fixTopic",        "href" => "/dev/methods/board/fixTopic"],
                        ["name" => "unfixTopic",      "href" => "/dev/methods/board/unfixTopic"],
                        ["name" => "deleteTopic",     "href" => "/dev/methods/board/deleteTopic"],
                    ],
                ],
                [
                    "id"    => "docs",
                    "name"  => "dsb_m_docs",
                    "links" => [
                        ["name" => "get",                 "href" => "/dev/methods/docs/get"],
                        ["name" => "getById",             "href" => "/dev/methods/docs/getById"],
                        ["name" => "getTypes",            "href" => "/dev/methods/docs/getTypes"],
                        ["name" => "getTags",             "href" => "/dev/methods/docs/getTags"],
                        ["name" => "search",              "href" => "/dev/methods/docs/search"],
                        ["name" => "add",                 "href" => "/dev/methods/docs/add"],
                        ["name" => "delete",              "href" => "/dev/methods/docs/delete"],
                        ["name" => "restore",             "href" => "/dev/methods/docs/restore"],
                        ["name" => "edit",                "href" => "/dev/methods/docs/edit"],
                        ["name" => "getUploadServer",     "href" => "/dev/methods/docs/getUploadServer"],
                        ["name" => "getWallUploadServer", "href" => "/dev/methods/docs/getWallUploadServer"],
                        ["name" => "save",                "href" => "/dev/methods/docs/save"],
                    ],
                ],
                [
                    "id"    => "friends",
                    "name"  => "dsb_m_friends",
                    "links" => [
                        ["name" => "get",            "href" => "/dev/methods/friends/get"],
                        ["name" => "getOnline",      "href" => "/dev/methods/friends/getOnline"],
                        ["name" => "getMutual",      "href" => "/dev/methods/friends/getMutual"],
                        ["name" => "getRequests",    "href" => "/dev/methods/friends/getRequests"],
                        ["name" => "getSuggestions", "href" => "/dev/methods/friends/getSuggestions"],
                        ["name" => "search",         "href" => "/dev/methods/friends/search"],
                        ["name" => "areFriends",     "href" => "/dev/methods/friends/areFriends"],
                        ["name" => "add",            "href" => "/dev/methods/friends/add"],
                        ["name" => "delete",         "href" => "/dev/methods/friends/delete"],
                        ["name" => "edit",           "href" => "/dev/methods/friends/edit"],
                        ["name" => "getLists",       "href" => "/dev/methods/friends/getLists"],
                        ["name" => "editList",       "href" => "/dev/methods/friends/editList"],
                        ["name" => "deleteList",     "href" => "/dev/methods/friends/deleteList"],
                    ],
                ],
                [
                    "id"    => "gifts",
                    "name"  => "dsb_m_gifts",
                    "links" => [
                        ["name" => "get",               "href" => "/dev/methods/gifts/get"],
                        ["name" => "send",              "href" => "/dev/methods/gifts/send"],
                        ["name" => "delete",            "href" => "/dev/methods/gifts/delete"],
                        ["name" => "getCategories",     "href" => "/dev/methods/gifts/getCategories"],
                        ["name" => "getGiftsInCategory", "href" => "/dev/methods/gifts/getGiftsInCategory"],
                    ],
                ],
                [
                    "id"    => "groups",
                    "name"  => "dsb_m_groups",
                    "links" => [
                        ["name" => "get",         "href" => "/dev/methods/groups/get"],
                        ["name" => "getById",     "href" => "/dev/methods/groups/getById"],
                        ["name" => "search",      "href" => "/dev/methods/groups/search"],
                        ["name" => "join",        "href" => "/dev/methods/groups/join"],
                        ["name" => "leave",       "href" => "/dev/methods/groups/leave"],
                        ["name" => "edit",        "href" => "/dev/methods/groups/edit"],
                        ["name" => "getMembers",  "href" => "/dev/methods/groups/getMembers"],
                        ["name" => "getSettings", "href" => "/dev/methods/groups/getSettings"],
                        ["name" => "isMember",    "href" => "/dev/methods/groups/isMember"],
                        ["name" => "ban",         "href" => "/dev/methods/groups/ban"],
                        ["name" => "unban",       "href" => "/dev/methods/groups/unban"],
                        ["name" => "getBanned",   "href" => "/dev/methods/groups/getBanned"],
                    ],
                ],
                [
                    "id"    => "users",
                    "name"  => "dsb_m_users",
                    "links" => [
                        ["name" => "get",              "href" => "/dev/methods/users/get"],
                        ["name" => "search",           "href" => "/dev/methods/users/search"],
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