OpenVK-KB-Heading: API Methods List

# API Methods List

To call any API method, send a GET or POST request to:
`https://{domain}/method/{method_name}`

---

## [execute](/dev/methods/execute)

* **[execute](/dev/methods/execute)** — Universal method for batch API executions and running VKScript algorithms in a single HTTP request.

---

## [account](/dev/methods/account)

* **[account.ban](/dev/methods/account/ban)** — Adds a user to the blacklist.
* **[account.unban](/dev/methods/account/unban)** — Removes a user from the blacklist.
* **[account.getBanned](/dev/methods/account/getBanned)** — Returns list of blacklisted users.
* **[account.getBalance](/dev/methods/account/getBalance)** — Returns current user's votes/balance.
* **[account.getCounters](/dev/methods/account/getCounters)** — Returns unread counters for messages, notifications, and requests.
* **[account.getInfo](/dev/methods/account/getInfo)** — Returns general account information and settings.
* **[account.getProfileInfo](/dev/methods/account/getProfileInfo)** — Returns extended user profile information.
* **[account.getOvkSettings](/dev/methods/account/getOvkSettings)** — Returns OpenVK-specific UI settings.
* **[account.getViewerId](/dev/methods/account/getViewerId)** — Returns current user ID.
* **[account.getAppPermissions](/dev/methods/account/getAppPermissions)** — Returns bitmask of app permissions and settings.
* **[account.saveProfileInfo](/dev/methods/account/saveProfileInfo)** — Saves basic profile details.
* **[account.saveInterestsInfo](/dev/methods/account/saveInterestsInfo)** — Saves interests and hobbies.
* **[account.sendVotes](/dev/methods/account/sendVotes)** — Transfers votes to another user.
* **[account.setOnline](/dev/methods/account/setOnline)** — Sets current user online for 5 minutes.
* **[account.setOffline](/dev/methods/account/setOffline)** — Sets current user offline.
* **[account.registerDevice](/dev/methods/account/registerDevice)** — Registers device for Push notifications.
* **[account.unregisterDevice](/dev/methods/account/unregisterDevice)** — Unregisters device from Push notifications.
* **[account.setSilenceMode](/dev/methods/account/setSilenceMode)** — Configures notification silence mode.
* **[account.getPushSettings](/dev/methods/account/getPushSettings)** — Returns Push notification settings.
* **[account.get](/dev/methods/account/get)** — Returns basic account object structure.
* **[account.getMulti](/dev/methods/account/getMulti)** — Returns multi-account session details.
* **[account.getPrivacySettings](/dev/methods/account/getPrivacySettings)** — Returns privacy settings structure.
* **[account.getContactList](/dev/methods/account/getContactList)** — Returns phone contacts list.
* **[account.getHelpHints](/dev/methods/account/getHelpHints)** — Returns help hints.
* **[account.getBadgesSettings](/dev/methods/account/getBadgesSettings)** — Returns app badges settings.
* **[account.getToggles](/dev/methods/account/getToggles)** — Returns feature toggles state.

## [activity](/dev/methods/activity)

* **[activity.online](/dev/methods/activity/online)** — Sets online activity with platform indicator.

## [apps](/dev/methods/apps)

* **[apps.getMiniAppsCatalog](/dev/methods/apps/getMiniAppsCatalog)** — Returns mini-apps catalog.
* **[apps.getMiniAppsCatalogSearch](/dev/methods/apps/getMiniAppsCatalogSearch)** — Searches mini-apps catalog.

## [audio](/dev/methods/audio)

* **[audio.get](/dev/methods/audio/get)** — Returns audio files of a user or community.
* **[audio.getById](/dev/methods/audio/getById)** — Returns information about audio files by their IDs.
* **[audio.search](/dev/methods/audio/search)** — Searches audio files.
* **[audio.getCount](/dev/methods/audio/getCount)** — Returns the number of audio files of a user or community.
* **[audio.getPopular](/dev/methods/audio/getPopular)** — Returns popular audio files.
* **[audio.getFeed](/dev/methods/audio/getFeed)** — Returns the feed of newly uploaded audio files.
* **[audio.getLyrics](/dev/methods/audio/getLyrics)** — Returns track lyrics by track ID.
* **[audio.add](/dev/methods/audio/add)** — Copies an audio file to user or community collection.
* **[audio.delete](/dev/methods/audio/delete)** — Removes an audio file from user or community collection.
* **[audio.restore](/dev/methods/audio/restore)** — Restores a deleted audio file.
* **[audio.edit](/dev/methods/audio/edit)** — Edits audio metadata.
* **[audio.setBroadcast](/dev/methods/audio/setBroadcast)** — Broadcasts current audio track to status.
* **[audio.getBroadcastList](/dev/methods/audio/getBroadcastList)** — Returns friends or communities broadcasting music to status.
* **[audio.beacon](/dev/methods/audio/beacon)** — Registers audio playback listen.
* **[audio.getAlbums](/dev/methods/audio/getAlbums)** — Returns playlists of a user or community.
* **[audio.getPlaylistById](/dev/methods/audio/getPlaylistById)** — Returns playlist information by ID.
* **[audio.searchAlbums](/dev/methods/audio/searchAlbums)** — Searches playlists.
* **[audio.addAlbum](/dev/methods/audio/addAlbum)** — Creates a new playlist.
* **[audio.editAlbum](/dev/methods/audio/editAlbum)** — Edits playlist title and description.
* **[audio.deleteAlbum](/dev/methods/audio/deleteAlbum)** — Deletes a playlist.
* **[audio.moveToAlbum](/dev/methods/audio/moveToAlbum)** — Adds audio files to a playlist or binds them to an album.
* **[audio.removeFromAlbum](/dev/methods/audio/removeFromAlbum)** — Removes audio files from a playlist.
* **[audio.bookmarkAlbum](/dev/methods/audio/bookmarkAlbum)** — Bookmarks a playlist.
* **[audio.unBookmarkAlbum](/dev/methods/audio/unBookmarkAlbum)** — Removes a playlist from bookmarks.
* **[audio.getRecommendations](/dev/methods/audio/getRecommendations)** — Returns recommended audio files.
* **[audio.isLagtrain](/dev/methods/audio/isLagtrain)** — Checks if audio track is Lagtrain.
* **[audio.subscribeToQueue](/dev/methods/audio/subscribeToQueue)** — Playback queue subscription stub.

## [board](/dev/methods/board)

* **[board.getTopics](/dev/methods/board/getTopics)** — Returns topics in a community discussion board.
* **[board.getComments](/dev/methods/board/getComments)** — Returns comments in a topic.
* **[board.addTopic](/dev/methods/board/addTopic)** — Creates a new topic in community discussions.
* **[board.addChatTopic](/dev/methods/board/addChatTopic)** — Creates a topic linked to a group chat.
* **[board.createComment](/dev/methods/board/createComment)** — Adds a comment to a topic.
* **[board.editTopic](/dev/methods/board/editTopic)** — Edits a topic title.
* **[board.closeTopic](/dev/methods/board/closeTopic)** — Closes a topic.
* **[board.openTopic](/dev/methods/board/openTopic)** — Re-opens a topic.
* **[board.fixTopic](/dev/methods/board/fixTopic)** — Pins a topic.
* **[board.unfixTopic](/dev/methods/board/unfixTopic)** — Unpins a topic.
* **[board.deleteTopic](/dev/methods/board/deleteTopic)** — Deletes a topic.

## [docs](/dev/methods/docs)

* **[docs.get](/dev/methods/docs/get)** — Returns documents of a user or community.
* **[docs.getById](/dev/methods/docs/getById)** — Returns information about documents by IDs.
* **[docs.getTypes](/dev/methods/docs/getTypes)** — Returns document type categories and file counts.
* **[docs.getTags](/dev/methods/docs/getTags)** — Returns tags used in user documents.
* **[docs.search](/dev/methods/docs/search)** — Searches documents.
* **[docs.add](/dev/methods/docs/add)** — Copies a document to the user collection.
* **[docs.edit](/dev/methods/docs/edit)** — Edits document metadata.
* **[docs.delete](/dev/methods/docs/delete)** — Deletes a document.
* **[docs.restore](/dev/methods/docs/restore)** — Restores a deleted document.
* **[docs.getUploadServer](/dev/methods/docs/getUploadServer)** — Returns document upload server address.
* **[docs.getWallUploadServer](/dev/methods/docs/getWallUploadServer)** — Returns wall document upload server address.
* **[docs.save](/dev/methods/docs/save)** — Saves an uploaded document.

## [friends](/dev/methods/friends)

* **[friends.get](/dev/methods/friends/get)** — Returns a list of user friend IDs or detailed user profiles.
* **[friends.getOnline](/dev/methods/friends/getOnline)** — Returns a list of IDs of friends who are currently online.
* **[friends.getMutual](/dev/methods/friends/getMutual)** — Returns a list of mutual friends between users.
* **[friends.getRequests](/dev/methods/friends/getRequests)** — Returns a list of incoming or outgoing friend requests.
* **[friends.getSuggestions](/dev/methods/friends/getSuggestions)** — Returns a list of friend suggestions (stub).
* **[friends.search](/dev/methods/friends/search)** — Searches through a user's friends list.
* **[friends.areFriends](/dev/methods/friends/areFriends)** — Returns friendship status with the specified users.
* **[friends.add](/dev/methods/friends/add)** — Sends a friend request or approves an incoming request.
* **[friends.delete](/dev/methods/friends/delete)** — Removes a user from friends or declines a request.
* **[friends.edit](/dev/methods/friends/edit)** — Edits friend lists of a friend (stub).
* **[friends.getLists](/dev/methods/friends/getLists)** — Returns friend lists (stub).
* **[friends.editList](/dev/methods/friends/editList)** — Edits a friend list (stub).
* **[friends.deleteList](/dev/methods/friends/deleteList)** — Deletes a friend list (stub).

## [gifts](/dev/methods/gifts)

* **[gifts.get](/dev/methods/gifts/get)** — Returns a list of gifts received by a user.
* **[gifts.send](/dev/methods/gifts/send)** — Sends a gift to a user for votes (coins).
* **[gifts.delete](/dev/methods/gifts/delete)** — Deletes a sent gift.
* **[gifts.getCategories](/dev/methods/gifts/getCategories)** — Returns the list of gift catalog categories.
* **[gifts.getGiftsInCategory](/dev/methods/gifts/getGiftsInCategory)** — Returns gifts available in a specific catalog category.

## [groups](/dev/methods/groups)

* **[groups.get](/dev/methods/groups/get)** — Returns a list of communities for a user.
* **[groups.getById](/dev/methods/groups/getById)** — Returns information about communities by IDs or short names.
* **[groups.search](/dev/methods/groups/search)** — Searches for communities on the platform.
* **[groups.join](/dev/methods/groups/join)** — Joins a group or subscribes to a public page.
* **[groups.leave](/dev/methods/groups/leave)** — Leaves a group or unsubscribes from a public page.
* **[groups.edit](/dev/methods/groups/edit)** — Edits main settings and parameters of a community.
* **[groups.getMembers](/dev/methods/groups/getMembers)** — Returns the list of community members (followers).
* **[groups.getSettings](/dev/methods/groups/getSettings)** — Returns current settings and access levels of community sections.
* **[groups.isMember](/dev/methods/groups/isMember)** — Checks whether a user is a member of a community.
* **[groups.ban](/dev/methods/groups/ban)** — Adds a user to the community blacklist.
* **[groups.unban](/dev/methods/groups/unban)** — Removes a user from the community blacklist.
* **[groups.getBanned](/dev/methods/groups/getBanned)** — Returns the list of users in the community blacklist.

## [internal](/dev/methods/internal)

* **[internal.getNotifications](/dev/methods/internal/getNotifications)** — Returns legacy mobile client service notifications (stub).
* **[internal.giveMeException](/dev/methods/internal/giveMeException)** — Triggers a test runtime exception.

## [likes](/dev/methods/likes)

* **[likes.add](/dev/methods/likes/add)** — Adds a "Like" reaction to the specified object.
* **[likes.delete](/dev/methods/likes/delete)** — Removes a "Like" reaction from the specified object.
* **[likes.isLiked](/dev/methods/likes/isLiked)** — Checks whether the specified object is in the user's liked list.
* **[likes.getList](/dev/methods/likes/getList)** — Returns a list of IDs or profiles of users who liked the object.

## [messages](/dev/methods/messages)

* **[messages.send](/dev/methods/messages/send)** — Sends a message, attachment, or sticker.
* **[messages.edit](/dev/methods/messages/edit)** — Edits a sent message.
* **[messages.delete](/dev/methods/messages/delete)** — Deletes messages.
* **[messages.restore](/dev/methods/messages/restore)** — Restores a deleted message.
* **[messages.get](/dev/methods/messages/get)** — Returns incoming/outgoing messages list.
* **[messages.getById](/dev/methods/messages/getById)** — Returns messages by IDs.
* **[messages.getByConversationMessageId](/dev/methods/messages/getByConversationMessageId)** — Returns messages by conversation message IDs.
* **[messages.getHistory](/dev/methods/messages/getHistory)** — Returns message history for a peer or chat.
* **[messages.getHistoryAttachments](/dev/methods/messages/getHistoryAttachments)** — Returns media attachments from history.
* **[messages.getImportantMessages](/dev/methods/messages/getImportantMessages)** — Returns important messages.
* **[messages.markAsRead](/dev/methods/messages/markAsRead)** — Marks messages as read.
* **[messages.markAsImportant](/dev/methods/messages/markAsImportant)** — Marks messages as important.
* **[messages.pin](/dev/methods/messages/pin)** — Pins a message in a conversation.
* **[messages.unpin](/dev/methods/messages/unpin)** — Unpins a message.
* **[messages.search](/dev/methods/messages/search)** — Searches messages by query string.
* **[messages.getConversations](/dev/methods/messages/getConversations)** — Returns modern conversations list (v >= 5.80).
* **[messages.getConversationsById](/dev/methods/messages/getConversationsById)** — Returns conversation objects by peer IDs.
* **[messages.searchConversations](/dev/methods/messages/searchConversations)** — Searches conversations.
* **[messages.getConversationMembers](/dev/methods/messages/getConversationMembers)** — Returns conversation members.
* **[messages.deleteConversation](/dev/methods/messages/deleteConversation)** — Deletes conversation history.
* **[messages.markAsImportantConversation](/dev/methods/messages/markAsImportantConversation)** — Marks conversation as important.
* **[messages.markAsAnsweredConversation](/dev/methods/messages/markAsAnsweredConversation)** — Marks conversation as answered.
* **[messages.getDialogs](/dev/methods/messages/getDialogs)** — Returns dialogues list (legacy).
* **[messages.searchDialogs](/dev/methods/messages/searchDialogs)** — Searches dialogues (legacy).
* **[messages.deleteDialog](/dev/methods/messages/deleteDialog)** — Deletes a dialogue (legacy).
* **[messages.createChat](/dev/methods/messages/createChat)** — Creates a multi-user chat.
* **[messages.getChat](/dev/methods/messages/getChat)** — Returns multi-user chat information.
* **[messages.getChatUsers](/dev/methods/messages/getChatUsers)** — Returns list of chat members.
* **[messages.addChatUser](/dev/methods/messages/addChatUser)** — Adds a user to a chat.
* **[messages.removeChatUser](/dev/methods/messages/removeChatUser)** — Removes a user from a chat.
* **[messages.editChat](/dev/methods/messages/editChat)** — Renames a multi-user chat.
* **[messages.setChatPhoto](/dev/methods/messages/setChatPhoto)** — Sets chat avatar photo.
* **[messages.deleteChatPhoto](/dev/methods/messages/deleteChatPhoto)** — Deletes chat avatar photo.
* **[messages.getInviteLink](/dev/methods/messages/getInviteLink)** — Returns invite link for a chat.
* **[messages.getChatPreview](/dev/methods/messages/getChatPreview)** — Returns preview for an invite link.
* **[messages.joinChatByInviteLink](/dev/methods/messages/joinChatByInviteLink)** — Joins a chat via invite link.
* **[messages.joinChatByTopic](/dev/methods/messages/joinChatByTopic)** — Joins a discussion-linked chat.
* **[messages.setMemberRole](/dev/methods/messages/setMemberRole)** — Sets role of a chat member.
* **[messages.setChatPermissions](/dev/methods/messages/setChatPermissions)** — Sets chat permissions.
* **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)** — Returns LongPoll server parameters.
* **[messages.getLongPollHistory](/dev/methods/messages/getLongPollHistory)** — Returns LongPoll history events.
* **[messages.getDiff](/dev/methods/messages/getDiff)** — Returns state diff for messenger clients.
* **[messages.setActivity](/dev/methods/messages/setActivity)** — Sends typing/voice recording status.
* **[messages.getLastActivity](/dev/methods/messages/getLastActivity)** — Returns user's last activity timestamp.
* **[messages.allowMessagesFromGroup](/dev/methods/messages/allowMessagesFromGroup)** — Allows messages from community.
* **[messages.denyMessagesFromGroup](/dev/methods/messages/denyMessagesFromGroup)** — Denies messages from community.
* **[messages.isMessagesFromGroupAllowed](/dev/methods/messages/isMessagesFromGroupAllowed)** — Checks if community messages are allowed.
* **[messages.createFolder](/dev/methods/messages/createFolder)** — Creates a chat folder.
* **[messages.getFolders](/dev/methods/messages/getFolders)** — Returns list of chat folders.
* **[messages.updateFolder](/dev/methods/messages/updateFolder)** — Updates a chat folder.
* **[messages.deleteFolder](/dev/methods/messages/deleteFolder)** — Deletes a chat folder.
* **[messages.reorderFolders](/dev/methods/messages/reorderFolders)** — Reorders chat folders.
* **[messages.getCounters](/dev/methods/messages/getCounters)** — Returns unread message counters.
* **[messages.getMessageViewers](/dev/methods/messages/getMessageViewers)** — Returns viewers of a message.
* **[messages.report](/dev/methods/messages/report)** — Reports a message as spam/abuse.

## [newsfeed](/dev/methods/newsfeed)

* **[newsfeed.get](/dev/methods/newsfeed/get)** — Returns posts, photos, and videos for the current user's newsfeed.
* **[newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal)** — Returns global feed of all public posts across the platform or an RSS feed.
* **[newsfeed.getRecommended](/dev/methods/newsfeed/getRecommended)** — Returns recommended posts (alias to newsfeed.getGlobal).
* **[newsfeed.search](/dev/methods/newsfeed/search)** — Searches posts in the newsfeed.
* **[newsfeed.getByType](/dev/methods/newsfeed/getByType)** — Returns newsfeed items by specified feed type.
* **[newsfeed.getComments](/dev/methods/newsfeed/getComments)** — Returns posts with recent comments from followed walls and discussions.
* **[newsfeed.getBanned](/dev/methods/newsfeed/getBanned)** — Returns the list of users and communities hidden from the newsfeed.
* **[newsfeed.addBan](/dev/methods/newsfeed/addBan)** — Hides posts from specified users or communities in the newsfeed.
* **[newsfeed.deleteBan](/dev/methods/newsfeed/deleteBan)** — Restores posts from specified users or communities in the newsfeed.
* **[newsfeed.getLists](/dev/methods/newsfeed/getLists)** — Returns custom newsfeed lists (stub).

## [users](/dev/methods/users)

* **[users.get](/dev/methods/users/get)** — Returns user profiles.
* **[users.search](/dev/methods/users/search)** — Searches for users.