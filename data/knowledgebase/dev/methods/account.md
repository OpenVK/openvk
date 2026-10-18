OpenVK-KB-Heading: Account Section Methods

# Account Section Methods

The **account** section contains API methods for managing the current user's account, profile details, blacklist, votes balance, device notifications, and settings.

### Profile & Details
* **[account.getProfileInfo](/dev/methods/account/getProfileInfo)** — returns detailed profile information of the current user.
* **[account.saveProfileInfo](/dev/methods/account/saveProfileInfo)** — saves core profile information (name, sex, birthday, status, etc.).
* **[account.saveInterestsInfo](/dev/methods/account/saveInterestsInfo)** — saves interests and activities (music, movies, books, etc.).
* **[account.getInfo](/dev/methods/account/getInfo)** — returns current account settings and preferences.
* **[account.getOvkSettings](/dev/methods/account/getOvkSettings)** — returns OpenVK-specific display and interface settings.
* **[account.getViewerId](/dev/methods/account/getViewerId)** — returns the ID of the current authorized user.
* **[account.getAppPermissions](/dev/methods/account/getAppPermissions)** — returns application permission bitmask.
* **[account.get](/dev/methods/account/get)** — returns basic account data structure.
* **[account.getMulti](/dev/methods/account/getMulti)** — returns current multi-account session details.

### Status & Activity
* **[account.setOnline](/dev/methods/account/setOnline)** — marks the user as online for 5 minutes.
* **[account.setOffline](/dev/methods/account/setOffline)** — marks the user as offline.
* **[account.getCounters](/dev/methods/account/getCounters)** — returns unread messages, notifications, and friend requests counters.

### Blacklist
* **[account.ban](/dev/methods/account/ban)** — adds a user to the blacklist.
* **[account.unban](/dev/methods/account/unban)** — removes a user from the blacklist.
* **[account.getBanned](/dev/methods/account/getBanned)** — returns the list of blacklisted users.

### Balance & Transfers
* **[account.getBalance](/dev/methods/account/getBalance)** — returns votes (coins) balance.
* **[account.sendVotes](/dev/methods/account/sendVotes)** — transfers votes to another user.

### Notifications & Devices
* **[account.registerDevice](/dev/methods/account/registerDevice)** — registers a device for push notifications.
* **[account.unregisterDevice](/dev/methods/account/unregisterDevice)** — unregisters a device from push notifications.
* **[account.setSilenceMode](/dev/methods/account/setSilenceMode)** — sets silence mode for notifications.
* **[account.getPushSettings](/dev/methods/account/getPushSettings)** — returns push notification settings.

### Privacy & System
* **[account.getPrivacySettings](/dev/methods/account/getPrivacySettings)** — returns account privacy settings.
* **[account.getContactList](/dev/methods/account/getContactList)** — returns contact list.
* **[account.getHelpHints](/dev/methods/account/getHelpHints)** — returns help hints.
* **[account.getBadgesSettings](/dev/methods/account/getBadgesSettings)** — returns badge configuration.
* **[account.getToggles](/dev/methods/account/getToggles)** — returns active feature toggles.