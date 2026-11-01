OpenVK-KB-Heading: friends Methods

# friends Methods

The **friends** section contains methods for managing user friends, searching friend lists, processing friend requests (incoming and outgoing), retrieving mutual and online friends, and checking friendship status between users.

### Retrieving and Searching Friends
* **[friends.get](/dev/methods/friends/get)** — Returns a list of user friend IDs or detailed user profile objects.
* **[friends.getOnline](/dev/methods/friends/getOnline)** — Returns a list of IDs of friends who are currently online.
* **[friends.getMutual](/dev/methods/friends/getMutual)** — Returns a list of mutual friends between the current (or specified) user and other users.
* **[friends.search](/dev/methods/friends/search)** — Searches through a user's friends list by name or surname substring.
* **[friends.getSuggestions](/dev/methods/friends/getSuggestions)** — Returns a list of friend recommendations (compatibility stub).

### Managing Requests and Friendship
* **[friends.getRequests](/dev/methods/friends/getRequests)** — Returns a list of incoming or outgoing friend requests.
* **[friends.areFriends](/dev/methods/friends/areFriends)** — Returns friendship status (friends, incoming request, outgoing request, not friends) with a list of users.
* **[friends.add](/dev/methods/friends/add)** — Sends a friend request or accepts an incoming request from a user.
* **[friends.delete](/dev/methods/friends/delete)** — Removes a user from friends or declines a request.

### Friend Lists (Stubs)
* **[friends.getLists](/dev/methods/friends/getLists)** — Returns friend lists / categories of a user.
* **[friends.edit](/dev/methods/friends/edit)** — Edits friend list assignments for a friend.
* **[friends.editList](/dev/methods/friends/editList)** — Edits the title of an existing friend list.
* **[friends.deleteList](/dev/methods/friends/deleteList)** — Deletes a friend list.
