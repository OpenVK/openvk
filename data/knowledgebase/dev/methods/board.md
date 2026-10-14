OpenVK-KB-Heading: board methods

# board methods

The **board** section provides methods for managing community discussion boards (topics), adding comments, pinning, closing, and binding group chats to discussions.

### Topics
* **[board.getTopics](/dev/methods/board/getTopics)** — returns a list of topics in a community board with first/last comment preview options.
* **[board.addTopic](/dev/methods/board/addTopic)** — creates a new topic in community discussions.
* **[board.addChatTopic](/dev/methods/board/addChatTopic)** — creates a discussion topic linked to a group chat.
* **[board.editTopic](/dev/methods/board/editTopic)** — edits the title of a topic.
* **[board.closeTopic](/dev/methods/board/closeTopic)** — closes a topic from new comments.
* **[board.openTopic](/dev/methods/board/openTopic)** — re-opens a closed topic.
* **[board.fixTopic](/dev/methods/board/fixTopic)** — pins a topic to the top of the discussion board.
* **[board.unfixTopic](/dev/methods/board/unfixTopic)** — unpins a topic.
* **[board.deleteTopic](/dev/methods/board/deleteTopic)** — deletes a topic.

### Comments in Topics
* **[board.getComments](/dev/methods/board/getComments)** — returns comments in a topic.
* **[board.createComment](/dev/methods/board/createComment)** — adds a new comment (text or sticker) to a topic.
