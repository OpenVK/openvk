OpenVK-KB-Heading: groups Methods

# groups Methods

The **groups** section contains methods for managing communities (groups and public pages): retrieving user communities, searching, viewing community profiles, managing membership, editing settings, retrieving members list, and managing community blacklists.

### Retrieving and Searching Communities
* **[groups.get](/dev/methods/groups/get)** — Returns the list of communities of the current or specified user.
* **[groups.getById](/dev/methods/groups/getById)** — Returns detailed information about communities by their IDs or short addresses (`screen_name`).
* **[groups.search](/dev/methods/groups/search)** — Searches for communities on the platform by keywords.

### Community Membership
* **[groups.isMember](/dev/methods/groups/isMember)** — Checks whether a user is a member/follower of a community.
* **[groups.join](/dev/methods/groups/join)** — Joins a group or subscribes to a public page.
* **[groups.leave](/dev/methods/groups/leave)** — Leaves a group or unsubscribes from a public page.
* **[groups.getMembers](/dev/methods/groups/getMembers)** — Returns the list of community members (followers).

### Community Management and Settings
* **[groups.edit](/dev/methods/groups/edit)** — Edits community metadata and settings (name, description, short URL, wall and section permissions).
* **[groups.getSettings](/dev/methods/groups/getSettings)** — Returns current settings and access levels of community sections.

### Community Blacklist
* **[groups.ban](/dev/methods/groups/ban)** — Bans a user in a community with optional reason, comment, and expiration date.
* **[groups.unban](/dev/methods/groups/unban)** — Unbans a user and removes them from the community blacklist.
* **[groups.getBanned](/dev/methods/groups/getBanned)** — Returns the list of users in the community blacklist.
