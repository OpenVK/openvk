OpenVK-KB-Heading: wall Methods

# wall Methods

The **wall** section provides methods for managing user and community walls: fetching posts, publishing, reposting, commenting, pinning, archiving, and discovering nearby geo-tagged posts.

## Method List

* **[wall.get](/dev/methods/wall/get)** — returns a list of posts from a user or community wall.
* **[wall.getArchiveYears](/dev/methods/wall/getArchiveYears)** — returns a list of years for which archived posts exist.
* **[wall.getById](/dev/methods/wall/getById)** — returns post objects by their identifiers.
* **[wall.post](/dev/methods/wall/post)** — publishes a new post on a user or community wall.
* **[wall.repost](/dev/methods/wall/repost)** — reposts an object (wall post, photo, video) to a user or community wall.
* **[wall.getComments](/dev/methods/wall/getComments)** — returns comments on a wall post.
* **[wall.getComment](/dev/methods/wall/getComment)** — returns detailed information about a specific comment.
* **[wall.createComment](/dev/methods/wall/createComment)** — creates a new comment on a wall post.
* **[wall.addComment](/dev/methods/wall/addComment)** — adds a comment to a wall post (alias for wall.createComment).
* **[wall.deleteComment](/dev/methods/wall/deleteComment)** — deletes a comment from a wall post.
* **[wall.delete](/dev/methods/wall/delete)** — deletes a post from a wall.
* **[wall.edit](/dev/methods/wall/edit)** — edits a post on a wall.
* **[wall.editComment](/dev/methods/wall/editComment)** — edits a comment on a wall post.
* **[wall.checkCopyrightLink](/dev/methods/wall/checkCopyrightLink)** — validates a copyright source link.
* **[wall.pin](/dev/methods/wall/pin)** — pins a post to the top of a wall.
* **[wall.unpin](/dev/methods/wall/unpin)** — unpins a post on a wall.
* **[wall.getNearby](/dev/methods/wall/getNearby)** — returns geo-tagged posts located near a specified post.
* **[wall.archive](/dev/methods/wall/archive)** — archives a wall post.
* **[wall.reveal](/dev/methods/wall/reveal)** — unarchives and restores a post to the wall.
* **[wall.getSubscriptions](/dev/methods/wall/getSubscriptions)** — returns subscriptions to wall updates.
