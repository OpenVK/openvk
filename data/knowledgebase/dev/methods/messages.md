OpenVK-KB-Heading: messages methods

# messages methods

The **messages** section contains API methods for working with direct messages, multi-user chats, modern conversations (dialogues), long polling, real-time message sync, attachments, and chat folders.

> **API Version Differences:**
> * **API 5.80 and higher (v >= 5.80):** Modern conversation model (`peer_id`, `conversation_message_id`, `messages.getConversations`, `messages.getConversationMembers`).
> * **API 5.0 — 5.79 (5.0 <= v < 5.80):** Classic message format with embedded chat metadata (`user_id`, `body`, `chat_id`, `chat_active`, `messages.getDialogs`).
> * **Legacy API (v < 5.0):** Legacy format (`mid`, `uid`, top-level array with count as first item `[count, item1, ...]`).

---

### Messages (Sending & Managing)
* **[messages.send](/dev/methods/messages/send)** — sends a message, attachment, or sticker to a user or chat.
* **[messages.edit](/dev/methods/messages/edit)** — edits a previously sent message text or attachments.
* **[messages.delete](/dev/methods/messages/delete)** — deletes messages for the current user or for all participants.
* **[messages.restore](/dev/methods/messages/restore)** — restores a deleted message.
* **[messages.markAsRead](/dev/methods/messages/markAsRead)** — marks messages as read.
* **[messages.markAsImportant](/dev/methods/messages/markAsImportant)** — marks or unmarks messages as important.
* **[messages.pin](/dev/methods/messages/pin)** — pins a message in a conversation.
* **[messages.unpin](/dev/methods/messages/unpin)** — unpins a pinned message.
* **[messages.report](/dev/methods/messages/report)** — reports a message as spam or abuse.

### Message Retrieval & Search
* **[messages.get](/dev/methods/messages/get)** — returns a list of incoming or outgoing messages.
* **[messages.getById](/dev/methods/messages/getById)** — returns messages by their global message IDs.
* **[messages.getByConversationMessageId](/dev/methods/messages/getByConversationMessageId)** — returns messages by local conversation message IDs.
* **[messages.getHistory](/dev/methods/messages/getHistory)** — returns message history for a dialogue or multi-user chat.
* **[messages.getHistoryAttachments](/dev/methods/messages/getHistoryAttachments)** — returns media attachments from message history.
* **[messages.getImportantMessages](/dev/methods/messages/getImportantMessages)** — returns messages marked as important.
* **[messages.search](/dev/methods/messages/search)** — searches for messages by query string.
* **[messages.getMessageViewers](/dev/methods/messages/getMessageViewers)** — returns users who read/viewed a specific message.

### Conversations (API 5.80+)
* **[messages.getConversations](/dev/methods/messages/getConversations)** — returns the list of modern conversations (dialogues and chats).
* **[messages.getConversationsById](/dev/methods/messages/getConversationsById)** — returns conversation objects by peer IDs.
* **[messages.searchConversations](/dev/methods/messages/searchConversations)** — searches conversations by name or title.
* **[messages.getConversationMembers](/dev/methods/messages/getConversationMembers)** — returns members and administrators of a conversation.
* **[messages.deleteConversation](/dev/methods/messages/deleteConversation)** — deletes all messages in a conversation.
* **[messages.markAsImportantConversation](/dev/methods/messages/markAsImportantConversation)** — marks a conversation as important.
* **[messages.markAsAnsweredConversation](/dev/methods/messages/markAsAnsweredConversation)** — marks a conversation as answered.

### Dialogues (Legacy API < 5.80)
* **[messages.getDialogs](/dev/methods/messages/getDialogs)** — returns the list of dialogues for legacy clients.
* **[messages.searchDialogs](/dev/methods/messages/searchDialogs)** — searches dialogues by query string.
* **[messages.deleteDialog](/dev/methods/messages/deleteDialog)** — deletes a dialogue.

### Multi-User Chats
* **[messages.createChat](/dev/methods/messages/createChat)** — creates a new multi-user chat.
* **[messages.getChat](/dev/methods/messages/getChat)** — returns chat information by chat ID.
* **[messages.getChatUsers](/dev/methods/messages/getChatUsers)** — returns the list of chat member IDs or profiles.
* **[messages.addChatUser](/dev/methods/messages/addChatUser)** — adds a user to a multi-user chat.
* **[messages.removeChatUser](/dev/methods/messages/removeChatUser)** — kicks a user or leaves a multi-user chat.
* **[messages.editChat](/dev/methods/messages/editChat)** — edits chat title.
* **[messages.setChatPhoto](/dev/methods/messages/setChatPhoto)** — uploads and sets a chat avatar photo.
* **[messages.deleteChatPhoto](/dev/methods/messages/deleteChatPhoto)** — deletes chat avatar photo.
* **[messages.getInviteLink](/dev/methods/messages/getInviteLink)** — generates or returns an invite link to a chat.
* **[messages.getChatPreview](/dev/methods/messages/getChatPreview)** — retrieves preview information for a chat invite link.
* **[messages.joinChatByInviteLink](/dev/methods/messages/joinChatByInviteLink)** — joins a chat via invite link.
* **[messages.joinChatByTopic](/dev/methods/messages/joinChatByTopic)** — joins a chat linked to a discussion topic.
* **[messages.setMemberRole](/dev/methods/messages/setMemberRole)** — changes role of a chat member (`admin` or `member`).
* **[messages.setChatPermissions](/dev/methods/messages/setChatPermissions)** — sets permissions for chat actions.

### Real-Time & LongPoll
* **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)** — returns LongPoll server parameters for real-time updates.
* **[messages.getLongPollHistory](/dev/methods/messages/getLongPollHistory)** — returns events from LongPoll history.
* **[messages.getDiff](/dev/methods/messages/getDiff)** — returns synchronized state diff for official messenger clients.
* **[messages.setActivity](/dev/methods/messages/setActivity)** — sends typing or audio recording status.
* **[messages.getLastActivity](/dev/methods/messages/getLastActivity)** — returns online/activity status of a user.

### Community Messaging
* **[messages.allowMessagesFromGroup](/dev/methods/messages/allowMessagesFromGroup)** — allows a community to send messages to the user.
* **[messages.denyMessagesFromGroup](/dev/methods/messages/denyMessagesFromGroup)** — denies messages from a community.
* **[messages.isMessagesFromGroupAllowed](/dev/methods/messages/isMessagesFromGroupAllowed)** — checks whether a community can send messages.

### Folders & Counters
* **[messages.createFolder](/dev/methods/messages/createFolder)** — creates a chat folder.
* **[messages.getFolders](/dev/methods/messages/getFolders)** — returns list of chat folders.
* **[messages.updateFolder](/dev/methods/messages/updateFolder)** — updates folder name and included chats.
* **[messages.deleteFolder](/dev/methods/messages/deleteFolder)** — deletes a chat folder.
* **[messages.reorderFolders](/dev/methods/messages/reorderFolders)** — reorders chat folders.
* **[messages.getCounters](/dev/methods/messages/getCounters)** — returns unread and unanswered message counters.
