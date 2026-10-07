OpenVK-KB-Heading: API Methods

# API Methods

To execute any method, send a GET or POST request to:
`https://{domain}/method/{method_name}`

## account

* **[account.ban](/dev/methods/account/ban)** — adds a user to the blacklist.
* **[account.unban](/dev/methods/account/unban)** — removes a user from the blacklist.
* **[account.getBanned](/dev/methods/account/getBanned)** — returns the list of blacklisted users.
* **[account.getBalance](/dev/methods/account/getBalance)** — returns the current user's votes (coins) balance.
* **[account.getCounters](/dev/methods/account/getCounters)** — returns unread messages, notifications, and friend requests counters.
* **[account.getInfo](/dev/methods/account/getInfo)** — returns current account settings and info.
* **[account.getProfileInfo](/dev/methods/account/getProfileInfo)** — returns detailed current user profile information.
* **[account.getOvkSettings](/dev/methods/account/getOvkSettings)** — returns OpenVK-specific user interface settings.
* **[account.getViewerId](/dev/methods/account/getViewerId)** — returns current user ID.
* **[account.getAppPermissions](/dev/methods/account/getAppPermissions)** — returns application permission bitmask.
* **[account.saveProfileInfo](/dev/methods/account/saveProfileInfo)** — saves core profile information.
* **[account.saveInterestsInfo](/dev/methods/account/saveInterestsInfo)** — saves interests and activities.
* **[account.sendVotes](/dev/methods/account/sendVotes)** — transfers votes to another user.
* **[account.setOnline](/dev/methods/account/setOnline)** — marks the current user as online.
* **[account.setOffline](/dev/methods/account/setOffline)** — marks the current user as offline.
* **[account.registerDevice](/dev/methods/account/registerDevice)** — registers a device for push notifications.
* **[account.unregisterDevice](/dev/methods/account/unregisterDevice)** — unregisters a device from push notifications.
* **[account.setSilenceMode](/dev/methods/account/setSilenceMode)** — sets silence mode for notifications.
* **[account.getPushSettings](/dev/methods/account/getPushSettings)** — returns push notification settings.
* **[account.get](/dev/methods/account/get)** — returns basic account data structure.
* **[account.getMulti](/dev/methods/account/getMulti)** — returns current multi-account session details.
* **[account.getPrivacySettings](/dev/methods/account/getPrivacySettings)** — returns account privacy settings.
* **[account.getContactList](/dev/methods/account/getContactList)** — returns contact list.
* **[account.getHelpHints](/dev/methods/account/getHelpHints)** — returns help hints.
* **[account.getBadgesSettings](/dev/methods/account/getBadgesSettings)** — returns badge configuration.
* **[account.getToggles](/dev/methods/account/getToggles)** — returns active feature toggles.

## audio

* **[audio.get](/dev/methods/audio/get)** — returns a list of audio files of a user or community.
* **[audio.search](/dev/methods/audio/search)** — returns search results for audio tracks.
* **[audio.add](/dev/methods/audio/add)** — copies an audio track to a user or community page.

## users

* **[users.get](/dev/methods/users/get)** — returns detailed information about users.
* **[users.search](/dev/methods/users/search)** — returns a list of users matching search criteria.