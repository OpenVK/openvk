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

    private function getList(string $name): void
    {
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
            ["name" => "dsb_openvk",     "href" => "/dev/openvk",     "type" => "link"],
            ["name" => "dsb_bugs",       "href" => "/dev/bugs",       "type" => "link"],
        ];

        $isMethodsPage = ($name === 'methods' || str_starts_with($name, 'methods/'));
        $this->template->isMethodsPage = $isMethodsPage;

        $isModelsPage = ($name === 'models' || str_starts_with($name, 'models/'));
        $this->template->isModelsPage = $isModelsPage;

        $isOpenvkPage = ($name === 'openvk' || str_starts_with($name, 'openvk/'));
        $this->template->isOpenvkPage = $isOpenvkPage;

        if ($isOpenvkPage) {
            $this->template->openvkDirectLinks = [
                ["name" => "dsb_ovk_intro",      "href" => "/dev/openvk"],
                ["name" => "dsb_ovk_contribute", "href" => "/dev/openvk/contribute"],
            ];

            $this->template->openvkGroups = [
                [
                    "id"    => "tutorials",
                    "name"  => "dsb_ovk_tutorials",
                    "links" => [
                        ["name" => "dsb_ovk_tut_quickstart", "href" => "/dev/openvk/tutorials/quickstart"],
                    ],
                ],
                [
                    "id"    => "chandler",
                    "name"  => "dsb_ovk_chandler",
                    "links" => [
                        ["name" => "dsb_ovk_ch_overview", "href" => "/dev/openvk/chandler/overview"],
                    ],
                ],
            ];
        }

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
                    "id"    => "internal",
                    "name"  => "dsb_m_internal",
                    "links" => [
                        ["name" => "getNotifications", "href" => "/dev/methods/internal/getNotifications"],
                        ["name" => "giveMeException",  "href" => "/dev/methods/internal/giveMeException"],
                    ],
                ],
                [
                    "id"    => "likes",
                    "name"  => "dsb_m_likes",
                    "links" => [
                        ["name" => "add",              "href" => "/dev/methods/likes/add"],
                        ["name" => "delete",           "href" => "/dev/methods/likes/delete"],
                        ["name" => "isLiked",          "href" => "/dev/methods/likes/isLiked"],
                        ["name" => "getList",          "href" => "/dev/methods/likes/getList"],
                    ],
                ],
                [
                    "id"    => "messages",
                    "name"  => "dsb_m_messages",
                    "links" => [
                        ["name" => "addChatUser",                 "href" => "/dev/methods/messages/addChatUser"],
                        ["name" => "allowMessagesFromGroup",      "href" => "/dev/methods/messages/allowMessagesFromGroup"],
                        ["name" => "createChat",                  "href" => "/dev/methods/messages/createChat"],
                        ["name" => "createFolder",                "href" => "/dev/methods/messages/createFolder"],
                        ["name" => "delete",                      "href" => "/dev/methods/messages/delete"],
                        ["name" => "deleteChatPhoto",             "href" => "/dev/methods/messages/deleteChatPhoto"],
                        ["name" => "deleteConversation",          "href" => "/dev/methods/messages/deleteConversation"],
                        ["name" => "deleteDialog",                "href" => "/dev/methods/messages/deleteDialog"],
                        ["name" => "deleteFolder",                "href" => "/dev/methods/messages/deleteFolder"],
                        ["name" => "denyMessagesFromGroup",       "href" => "/dev/methods/messages/denyMessagesFromGroup"],
                        ["name" => "edit",                        "href" => "/dev/methods/messages/edit"],
                        ["name" => "editChat",                    "href" => "/dev/methods/messages/editChat"],
                        ["name" => "get",                         "href" => "/dev/methods/messages/get"],
                        ["name" => "getByConversationMessageId", "href" => "/dev/methods/messages/getByConversationMessageId"],
                        ["name" => "getById",                     "href" => "/dev/methods/messages/getById"],
                        ["name" => "getChat",                     "href" => "/dev/methods/messages/getChat"],
                        ["name" => "getChatPreview",              "href" => "/dev/methods/messages/getChatPreview"],
                        ["name" => "getChatUsers",                "href" => "/dev/methods/messages/getChatUsers"],
                        ["name" => "getConversationMembers",      "href" => "/dev/methods/messages/getConversationMembers"],
                        ["name" => "getConversations",            "href" => "/dev/methods/messages/getConversations"],
                        ["name" => "getConversationsById",        "href" => "/dev/methods/messages/getConversationsById"],
                        ["name" => "getCounters",                 "href" => "/dev/methods/messages/getCounters"],
                        ["name" => "getDialogs",                  "href" => "/dev/methods/messages/getDialogs"],
                        ["name" => "getDiff",                     "href" => "/dev/methods/messages/getDiff"],
                        ["name" => "getFolders",                  "href" => "/dev/methods/messages/getFolders"],
                        ["name" => "getHistory",                  "href" => "/dev/methods/messages/getHistory"],
                        ["name" => "getHistoryAttachments",       "href" => "/dev/methods/messages/getHistoryAttachments"],
                        ["name" => "getImportantMessages",        "href" => "/dev/methods/messages/getImportantMessages"],
                        ["name" => "getInviteLink",               "href" => "/dev/methods/messages/getInviteLink"],
                        ["name" => "getLastActivity",             "href" => "/dev/methods/messages/getLastActivity"],
                        ["name" => "getLongPollHistory",          "href" => "/dev/methods/messages/getLongPollHistory"],
                        ["name" => "getLongPollServer",           "href" => "/dev/methods/messages/getLongPollServer"],
                        ["name" => "getMessageViewers",           "href" => "/dev/methods/messages/getMessageViewers"],
                        ["name" => "isMessagesFromGroupAllowed",  "href" => "/dev/methods/messages/isMessagesFromGroupAllowed"],
                        ["name" => "joinChatByInviteLink",        "href" => "/dev/methods/messages/joinChatByInviteLink"],
                        ["name" => "joinChatByTopic",             "href" => "/dev/methods/messages/joinChatByTopic"],
                        ["name" => "markAsAnsweredConversation",  "href" => "/dev/methods/messages/markAsAnsweredConversation"],
                        ["name" => "markAsImportant",             "href" => "/dev/methods/messages/markAsImportant"],
                        ["name" => "markAsImportantConversation", "href" => "/dev/methods/messages/markAsImportantConversation"],
                        ["name" => "markAsRead",                  "href" => "/dev/methods/messages/markAsRead"],
                        ["name" => "pin",                         "href" => "/dev/methods/messages/pin"],
                        ["name" => "removeChatUser",              "href" => "/dev/methods/messages/removeChatUser"],
                        ["name" => "reorderFolders",              "href" => "/dev/methods/messages/reorderFolders"],
                        ["name" => "report",                      "href" => "/dev/methods/messages/report"],
                        ["name" => "restore",                     "href" => "/dev/methods/messages/restore"],
                        ["name" => "search",                      "href" => "/dev/methods/messages/search"],
                        ["name" => "searchConversations",         "href" => "/dev/methods/messages/searchConversations"],
                        ["name" => "searchDialogs",               "href" => "/dev/methods/messages/searchDialogs"],
                        ["name" => "send",                        "href" => "/dev/methods/messages/send"],
                        ["name" => "setActivity",                 "href" => "/dev/methods/messages/setActivity"],
                        ["name" => "setChatPermissions",          "href" => "/dev/methods/messages/setChatPermissions"],
                        ["name" => "setChatPhoto",                "href" => "/dev/methods/messages/setChatPhoto"],
                        ["name" => "setMemberRole",               "href" => "/dev/methods/messages/setMemberRole"],
                        ["name" => "unpin",                       "href" => "/dev/methods/messages/unpin"],
                        ["name" => "updateFolder",                "href" => "/dev/methods/messages/updateFolder"],
                    ],
                ],
                [
                    "id"    => "newsfeed",
                    "name"  => "dsb_m_newsfeed",
                    "links" => [
                        ["name" => "addBan",         "href" => "/dev/methods/newsfeed/addBan"],
                        ["name" => "deleteBan",      "href" => "/dev/methods/newsfeed/deleteBan"],
                        ["name" => "get",            "href" => "/dev/methods/newsfeed/get"],
                        ["name" => "getBanned",      "href" => "/dev/methods/newsfeed/getBanned"],
                        ["name" => "getByType",      "href" => "/dev/methods/newsfeed/getByType"],
                        ["name" => "getComments",    "href" => "/dev/methods/newsfeed/getComments"],
                        ["name" => "getGlobal",      "href" => "/dev/methods/newsfeed/getGlobal"],
                        ["name" => "getLists",       "href" => "/dev/methods/newsfeed/getLists"],
                        ["name" => "getRecommended", "href" => "/dev/methods/newsfeed/getRecommended"],
                        ["name" => "search",         "href" => "/dev/methods/newsfeed/search"],
                    ],
                ],
                [
                    "id"    => "notes",
                    "name"  => "dsb_m_notes",
                    "links" => [
                        ["name" => "add",           "href" => "/dev/methods/notes/add"],
                        ["name" => "addComment",    "href" => "/dev/methods/notes/addComment"],
                        ["name" => "createComment", "href" => "/dev/methods/notes/createComment"],
                        ["name" => "delete",        "href" => "/dev/methods/notes/delete"],
                        ["name" => "edit",          "href" => "/dev/methods/notes/edit"],
                        ["name" => "get",           "href" => "/dev/methods/notes/get"],
                        ["name" => "getById",       "href" => "/dev/methods/notes/getById"],
                        ["name" => "getComments",   "href" => "/dev/methods/notes/getComments"],
                    ],
                ],
                [
                    "id"    => "notifications",
                    "name"  => "dsb_m_notifications",
                    "links" => [
                        ["name" => "fetch",             "href" => "/dev/methods/notifications/fetch"],
                        ["name" => "get",               "href" => "/dev/methods/notifications/get"],
                        ["name" => "getIgnoredSources", "href" => "/dev/methods/notifications/getIgnoredSources"],
                        ["name" => "getSettings",       "href" => "/dev/methods/notifications/getSettings"],
                        ["name" => "markAsViewed",      "href" => "/dev/methods/notifications/markAsViewed"],
                    ],
                ],
                [
                    "id"    => "pay",
                    "name"  => "dsb_m_pay",
                    "links" => [
                        ["name" => "getIdByMarketingId", "href" => "/dev/methods/pay/getIdByMarketingId"],
                        ["name" => "verifyOrder",        "href" => "/dev/methods/pay/verifyOrder"],
                    ],
                ],
                [
                    "id"    => "photos",
                    "name"  => "dsb_m_photos",
                    "links" => [
                        ["name" => "addComment",                 "href" => "/dev/methods/photos/addComment"],
                        ["name" => "confirmTag",                 "href" => "/dev/methods/photos/confirmTag"],
                        ["name" => "createAlbum",                "href" => "/dev/methods/photos/createAlbum"],
                        ["name" => "createComment",              "href" => "/dev/methods/photos/createComment"],
                        ["name" => "delete",                     "href" => "/dev/methods/photos/delete"],
                        ["name" => "deleteAlbum",                "href" => "/dev/methods/photos/deleteAlbum"],
                        ["name" => "deleteTag",                  "href" => "/dev/methods/photos/deleteTag"],
                        ["name" => "edit",                       "href" => "/dev/methods/photos/edit"],
                        ["name" => "editAlbum",                  "href" => "/dev/methods/photos/editAlbum"],
                        ["name" => "get",                        "href" => "/dev/methods/photos/get"],
                        ["name" => "getAlbums",                  "href" => "/dev/methods/photos/getAlbums"],
                        ["name" => "getAlbumsCount",             "href" => "/dev/methods/photos/getAlbumsCount"],
                        ["name" => "getAll",                     "href" => "/dev/methods/photos/getAll"],
                        ["name" => "getById",                    "href" => "/dev/methods/photos/getById"],
                        ["name" => "getChatUploadServer",        "href" => "/dev/methods/photos/getChatUploadServer"],
                        ["name" => "getComments",                "href" => "/dev/methods/photos/getComments"],
                        ["name" => "getMessagesUploadServer",    "href" => "/dev/methods/photos/getMessagesUploadServer"],
                        ["name" => "getOwnerPhotoUploadServer",  "href" => "/dev/methods/photos/getOwnerPhotoUploadServer"],
                        ["name" => "getTags",                    "href" => "/dev/methods/photos/getTags"],
                        ["name" => "getUploadServer",            "href" => "/dev/methods/photos/getUploadServer"],
                        ["name" => "getUserPhotos",              "href" => "/dev/methods/photos/getUserPhotos"],
                        ["name" => "getWallUploadServer",        "href" => "/dev/methods/photos/getWallUploadServer"],
                        ["name" => "putTag",                     "href" => "/dev/methods/photos/putTag"],
                        ["name" => "save",                       "href" => "/dev/methods/photos/save"],
                        ["name" => "saveMessagesPhoto",          "href" => "/dev/methods/photos/saveMessagesPhoto"],
                        ["name" => "saveOwnerPhoto",             "href" => "/dev/methods/photos/saveOwnerPhoto"],
                        ["name" => "saveWallPhoto",              "href" => "/dev/methods/photos/saveWallPhoto"],
                    ],
                ],
                [
                    "id"    => "places",
                    "name"  => "dsb_m_places",
                    "links" => [
                        ["name" => "checkin",          "href" => "/dev/methods/places/checkin"],
                        ["name" => "getCitiesById",    "href" => "/dev/methods/places/getCitiesById"],
                        ["name" => "getCityById",      "href" => "/dev/methods/places/getCityById"],
                        ["name" => "getCountriesById", "href" => "/dev/methods/places/getCountriesById"],
                        ["name" => "getCountryById",   "href" => "/dev/methods/places/getCountryById"],
                        ["name" => "getCheckins",      "href" => "/dev/methods/places/getCheckins"],
                    ],
                ],
                [
                    "id"    => "polls",
                    "name"  => "dsb_m_polls",
                    "links" => [
                        ["name" => "addVote",    "href" => "/dev/methods/polls/addVote"],
                        ["name" => "create",     "href" => "/dev/methods/polls/create"],
                        ["name" => "deleteVote", "href" => "/dev/methods/polls/deleteVote"],
                        ["name" => "getById",    "href" => "/dev/methods/polls/getById"],
                        ["name" => "getVoters",  "href" => "/dev/methods/polls/getVoters"],
                    ],
                ],
                [
                    "id"    => "queue",
                    "name"  => "dsb_m_queue",
                    "links" => [
                        ["name" => "subscribe",   "href" => "/dev/methods/queue/subscribe"],
                        ["name" => "unsubscribe", "href" => "/dev/methods/queue/unsubscribe"],
                    ],
                ],
                [
                    "id"    => "reports",
                    "name"  => "dsb_m_reports",
                    "links" => [
                        ["name" => "add", "href" => "/dev/methods/reports/add"],
                    ],
                ],
                [
                    "id"    => "search",
                    "name"  => "dsb_m_search",
                    "links" => [
                        ["name" => "getHints", "href" => "/dev/methods/search/getHints"],
                    ],
                ],
                [
                    "id"    => "stats",
                    "name"  => "dsb_m_stats",
                    "links" => [
                        ["name" => "trackEvents", "href" => "/dev/methods/stats/trackEvents"],
                    ],
                ],
                [
                    "id"    => "status",
                    "name"  => "dsb_m_status",
                    "links" => [
                        ["name" => "get", "href" => "/dev/methods/status/get"],
                        ["name" => "set", "href" => "/dev/methods/status/set"],
                    ],
                ],
                [
                    "id"    => "stickers",
                    "name"  => "dsb_m_stickers",
                    "links" => [
                        ["name" => "buy",                  "href" => "/dev/methods/stickers/buy"],
                        ["name" => "get",                  "href" => "/dev/methods/stickers/get"],
                        ["name" => "getAll",               "href" => "/dev/methods/stickers/getAll"],
                        ["name" => "getFrom",              "href" => "/dev/methods/stickers/getFrom"],
                        ["name" => "getKeywordStickers",   "href" => "/dev/methods/stickers/getKeywordStickers"],
                        ["name" => "getProducts",          "href" => "/dev/methods/stickers/getProducts"],
                        ["name" => "getStickersKeywords",  "href" => "/dev/methods/stickers/getStickersKeywords"],
                        ["name" => "getStockItems",        "href" => "/dev/methods/stickers/getStockItems"],
                    ],
                ],
                [
                    "id"    => "store",
                    "name"  => "dsb_m_store",
                    "links" => [
                        ["name" => "activateProduct",       "href" => "/dev/methods/store/activateProduct"],
                        ["name" => "addFavoriteSticker",    "href" => "/dev/methods/store/addFavoriteSticker"],
                        ["name" => "addRecentSticker",      "href" => "/dev/methods/store/addRecentSticker"],
                        ["name" => "buy",                   "href" => "/dev/methods/store/buy"],
                        ["name" => "clearRecentStickers",   "href" => "/dev/methods/store/clearRecentStickers"],
                        ["name" => "deactivateProduct",     "href" => "/dev/methods/store/deactivateProduct"],
                        ["name" => "getFavoriteStickers",   "href" => "/dev/methods/store/getFavoriteStickers"],
                        ["name" => "getProducts",           "href" => "/dev/methods/store/getProducts"],
                        ["name" => "getRecentStickers",     "href" => "/dev/methods/store/getRecentStickers"],
                        ["name" => "getStickersKeywords",   "href" => "/dev/methods/store/getStickersKeywords"],
                        ["name" => "getStockItems",         "href" => "/dev/methods/store/getStockItems"],
                        ["name" => "removeFavoriteSticker", "href" => "/dev/methods/store/removeFavoriteSticker"],
                    ],
                ],
                [
                    "id"    => "users",
                    "name"  => "dsb_m_users",
                    "links" => [
                        ["name" => "get",              "href" => "/dev/methods/users/get"],
                        ["name" => "getFollowers",     "href" => "/dev/methods/users/getFollowers"],
                        ["name" => "report",           "href" => "/dev/methods/users/report"],
                        ["name" => "search",           "href" => "/dev/methods/users/search"],
                    ],
                ],
                [
                    "id"    => "utils",
                    "name"  => "dsb_m_utils",
                    "links" => [
                        ["name" => "getServerTime",      "href" => "/dev/methods/utils/getServerTime"],
                        ["name" => "resolveAttachments", "href" => "/dev/methods/utils/resolveAttachments"],
                        ["name" => "resolveGuid",        "href" => "/dev/methods/utils/resolveGuid"],
                        ["name" => "resolveOffset",      "href" => "/dev/methods/utils/resolveOffset"],
                        ["name" => "resolveScreenName",  "href" => "/dev/methods/utils/resolveScreenName"],
                    ],
                ],
                [
                    "id"    => "video",
                    "name"  => "dsb_m_video",
                    "links" => [
                        ["name" => "addComment",         "href" => "/dev/methods/video/addComment"],
                        ["name" => "createComment",      "href" => "/dev/methods/video/createComment"],
                        ["name" => "delete",             "href" => "/dev/methods/video/delete"],
                        ["name" => "deleteComment",      "href" => "/dev/methods/video/deleteComment"],
                        ["name" => "edit",               "href" => "/dev/methods/video/edit"],
                        ["name" => "get",                "href" => "/dev/methods/video/get"],
                        ["name" => "getComments",        "href" => "/dev/methods/video/getComments"],
                        ["name" => "getUserVideos",      "href" => "/dev/methods/video/getUserVideos"],
                        ["name" => "search",             "href" => "/dev/methods/video/search"],
                    ],
                ],
                [
                    "id"    => "wall",
                    "name"  => "dsb_m_wall",
                    "links" => [
                        ["name" => "addComment",         "href" => "/dev/methods/wall/addComment"],
                        ["name" => "archive",            "href" => "/dev/methods/wall/archive"],
                        ["name" => "checkCopyrightLink", "href" => "/dev/methods/wall/checkCopyrightLink"],
                        ["name" => "createComment",      "href" => "/dev/methods/wall/createComment"],
                        ["name" => "delete",             "href" => "/dev/methods/wall/delete"],
                        ["name" => "deleteComment",      "href" => "/dev/methods/wall/deleteComment"],
                        ["name" => "edit",               "href" => "/dev/methods/wall/edit"],
                        ["name" => "editComment",        "href" => "/dev/methods/wall/editComment"],
                        ["name" => "get",                "href" => "/dev/methods/wall/get"],
                        ["name" => "getArchiveYears",    "href" => "/dev/methods/wall/getArchiveYears"],
                        ["name" => "getById",            "href" => "/dev/methods/wall/getById"],
                        ["name" => "getComment",         "href" => "/dev/methods/wall/getComment"],
                        ["name" => "getComments",        "href" => "/dev/methods/wall/getComments"],
                        ["name" => "getNearby",          "href" => "/dev/methods/wall/getNearby"],
                        ["name" => "getSubscriptions",   "href" => "/dev/methods/wall/getSubscriptions"],
                        ["name" => "pin",                "href" => "/dev/methods/wall/pin"],
                        ["name" => "post",               "href" => "/dev/methods/wall/post"],
                        ["name" => "repost",             "href" => "/dev/methods/wall/repost"],
                        ["name" => "reveal",             "href" => "/dev/methods/wall/reveal"],
                        ["name" => "unpin",              "href" => "/dev/methods/wall/unpin"],
                    ],
                ],
                // OpenVK-specific methods are intentionally placed at the end to separate them from official VK API methods
                [
                    "id"    => "ovk",
                    "name"  => "dsb_m_ovk",
                    "links" => [
                        ["name" => "aboutInstance", "href" => "/dev/methods/ovk/aboutInstance"],
                        ["name" => "chickenWings",  "href" => "/dev/methods/ovk/chickenWings"],
                        ["name" => "getMirrors",    "href" => "/dev/methods/ovk/getMirrors"],
                        ["name" => "nuggets",       "href" => "/dev/methods/ovk/nuggets"],
                        ["name" => "test",          "href" => "/dev/methods/ovk/test"],
                        ["name" => "version",       "href" => "/dev/methods/ovk/version"],
                    ],
                ],
            ];
        }
    }

    public function renderIndex(): void
    {
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
