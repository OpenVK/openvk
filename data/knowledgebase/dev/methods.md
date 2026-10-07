OpenVK-KB-Heading: API Methods List

# API Methods List

To call any API method, send a GET or POST request to:
`https://{domain}/method/{method_name}`

---

## execute

* **[execute](/dev/methods/execute)** — Universal method for batch API executions and running VKScript algorithms in a single HTTP request.

---

## account

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

## activity

* **[activity.online](/dev/methods/activity/online)** — Sets online activity with platform indicator.

## apps

* **[apps.getMiniAppsCatalog](/dev/methods/apps/getMiniAppsCatalog)** — Returns mini-apps catalog.
* **[apps.getMiniAppsCatalogSearch](/dev/methods/apps/getMiniAppsCatalogSearch)** — Searches mini-apps catalog.

## audio

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

## board

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

## users

* **[users.get](/dev/methods/users/get)** — Returns user profiles.
* **[users.search](/dev/methods/users/search)** — Searches for users.