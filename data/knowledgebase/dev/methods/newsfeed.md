OpenVK-KB-Heading: newsfeed Methods

# newsfeed Methods

The **newsfeed** section contains methods for managing and viewing newsfeeds: retrieving customized and global feeds, viewing recent comments, searching news, and managing the list of banned (ignored) sources.

## Method List

* **[newsfeed.get](/dev/methods/newsfeed/get)** — returns posts, photos, and videos for the current user's newsfeed.
* **[newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal)** — returns global feed of all public posts across the platform or an RSS feed.
* **[newsfeed.getRecommended](/dev/methods/newsfeed/getRecommended)** — returns recommended posts (alias to `newsfeed.getGlobal`).
* **[newsfeed.search](/dev/methods/newsfeed/search)** — searches posts in the newsfeed.
* **[newsfeed.getByType](/dev/methods/newsfeed/getByType)** — returns newsfeed items by specified feed type (`top` or standard feed).
* **[newsfeed.getComments](/dev/methods/newsfeed/getComments)** — returns posts with recent comments from followed walls and discussions.
* **[newsfeed.getBanned](/dev/methods/newsfeed/getBanned)** — returns the list of users and communities hidden from the newsfeed.
* **[newsfeed.addBan](/dev/methods/newsfeed/addBan)** — hides posts from specified users or communities in the newsfeed.
* **[newsfeed.deleteBan](/dev/methods/newsfeed/deleteBan)** — restores posts from specified users or communities in the newsfeed.
* **[newsfeed.getLists](/dev/methods/newsfeed/getLists)** — returns custom newsfeed lists (stub).
