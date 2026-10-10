OpenVK-KB-Heading: Методы секции messages

# Методы секции messages

Секция **messages** содержит методы для работы с личными сообщениями, групповыми беседами (чатами), современными диалогами (беседами), LongPoll сервером, синхронизацией сообщений, вложениями и папками диалогов.

> **Различия по версиям API:**
> * **API версии 5.80 и выше (v >= 5.80):** Современная модель бесед (`peer_id`, `conversation_message_id`, `messages.getConversations`, `messages.getConversationMembers`).
> * **API версий 5.0 — 5.79 (5.0 <= v < 5.80):** Классический формат сообщений со встроенными полями чата (`user_id`, `body`, `chat_id`, `chat_active`, `messages.getDialogs`).
> * **Устаревшие версии API (v < 5.0):** Устаревший формат (`mid`, `uid`, ответ в виде массива с количеством первым элементом: `[count, item1, ...]`).

---

### Отправка и управление сообщениями
* **[messages.send](/dev/methods/messages/send)** — отправляет сообщение, вложение или стикер пользователю либо в беседу.
* **[messages.edit](/dev/methods/messages/edit)** — редактирует текст или вложения отправленного ранее сообщения.
* **[messages.delete](/dev/methods/messages/delete)** — удаляет сообщения для текущего пользователя или для всех участников.
* **[messages.restore](/dev/methods/messages/restore)** — восстанавливает удаленное сообщение.
* **[messages.markAsRead](/dev/methods/messages/markAsRead)** — помечает сообщения как прочитанные.
* **[messages.markAsImportant](/dev/methods/messages/markAsImportant)** — помечает сообщения как важные или снимает отметку.
* **[messages.pin](/dev/methods/messages/pin)** — закрепляет сообщение в беседе.
* **[messages.unpin](/dev/methods/messages/unpin)** — открепляет сообщение в беседе.
* **[messages.report](/dev/methods/messages/report)** — отправляет жалобу на спам или нарушение в сообщении.

### Получение и поиск сообщений
* **[messages.get](/dev/methods/messages/get)** — возвращает список входящих или исходящих сообщений.
* **[messages.getById](/dev/methods/messages/getById)** — возвращает сообщения по их глобальным идентификаторам.
* **[messages.getByConversationMessageId](/dev/methods/messages/getByConversationMessageId)** — возвращает сообщения по локальным идентификаторам беседы.
* **[messages.getHistory](/dev/methods/messages/getHistory)** — возвращает историю сообщений диалога или беседы.
* **[messages.getHistoryAttachments](/dev/methods/messages/getHistoryAttachments)** — возвращает медиавложения из истории сообщений.
* **[messages.getImportantMessages](/dev/methods/messages/getImportantMessages)** — возвращает сообщения, помеченные как важные.
* **[messages.search](/dev/methods/messages/search)** — осуществляет поиск по тексту сообщений.
* **[messages.getMessageViewers](/dev/methods/messages/getMessageViewers)** — возвращает список пользователей, прочитавших конкретное сообщение.

### Беседы и диалоги (API 5.80+)
* **[messages.getConversations](/dev/methods/messages/getConversations)** — возвращает список бесед и диалогов в современном формате 5.80+.
* **[messages.getConversationsById](/dev/methods/messages/getConversationsById)** — возвращает информацию о беседах по их `peer_id`.
* **[messages.searchConversations](/dev/methods/messages/searchConversations)** — осуществляет поиск по беседам и контактам.
* **[messages.getConversationMembers](/dev/methods/messages/getConversationMembers)** — возвращает участников и администраторов беседы.
* **[messages.deleteConversation](/dev/methods/messages/deleteConversation)** — удаляет всю переписку в беседе.
* **[messages.markAsImportantConversation](/dev/methods/messages/markAsImportantConversation)** — помечает беседу как важную.
* **[messages.markAsAnsweredConversation](/dev/methods/messages/markAsAnsweredConversation)** — помечает беседу как отвеченную.

### Диалоги (Устаревшие версии API < 5.80)
* **[messages.getDialogs](/dev/methods/messages/getDialogs)** — возвращает список последних сообщений диалогов для старых клиентов.
* **[messages.searchDialogs](/dev/methods/messages/searchDialogs)** — осуществляет поиск по диалогам для старых клиентов.
* **[messages.deleteDialog](/dev/methods/messages/deleteDialog)** — удаляет диалог.

### Групповые беседы (Мультидиалоги)
* **[messages.createChat](/dev/methods/messages/createChat)** — создает новую групповую беседу (чат).
* **[messages.getChat](/dev/methods/messages/getChat)** — возвращает информацию о беседе по ее идентификатору.
* **[messages.getChatUsers](/dev/methods/messages/getChatUsers)** — возвращает список участников беседы.
* **[messages.addChatUser](/dev/methods/messages/addChatUser)** — добавляет пользователя в беседу.
* **[messages.removeChatUser](/dev/methods/messages/removeChatUser)** — исключает пользователя из беседы или покидает ее.
* **[messages.editChat](/dev/methods/messages/editChat)** — изменяет название беседы.
* **[messages.setChatPhoto](/dev/methods/messages/setChatPhoto)** — загружает и устанавливает обложку беседы.
* **[messages.deleteChatPhoto](/dev/methods/messages/deleteChatPhoto)** — удаляет обложку беседы.
* **[messages.getInviteLink](/dev/methods/messages/getInviteLink)** — генерирует или возвращает ссылку-приглашение в беседу.
* **[messages.getChatPreview](/dev/methods/messages/getChatPreview)** — возвращает предпросмотр беседы по ссылке-приглашению.
* **[messages.joinChatByInviteLink](/dev/methods/messages/joinChatByInviteLink)** — присоединяется к беседе по ссылке-приглашению.
* **[messages.joinChatByTopic](/dev/methods/messages/joinChatByTopic)** — присоединяется к беседе, привязанной к теме обсуждений.
* **[messages.setMemberRole](/dev/methods/messages/setMemberRole)** — изменяет роль участника в беседе (`admin` или `member`).
* **[messages.setChatPermissions](/dev/methods/messages/setChatPermissions)** — настраивает права доступа на действия в беседе.

### Real-Time и LongPoll
* **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)** — возвращает адрес и ключи подключения к LongPoll серверу.
* **[messages.getLongPollHistory](/dev/methods/messages/getLongPollHistory)** — возвращает историю событий из LongPoll.
* **[messages.getDiff](/dev/methods/messages/getDiff)** — возвращает синхронизацию состояния мессенджера (дифф) для официальных клиентов.
* **[messages.setActivity](/dev/methods/messages/setActivity)** — отправляет статус набора текста или записи голосового сообщения.
* **[messages.getLastActivity](/dev/methods/messages/getLastActivity)** — возвращает время последней активности и статус онлайн пользователя.

### Сообщения сообществ
* **[messages.allowMessagesFromGroup](/dev/methods/messages/allowMessagesFromGroup)** — разрешает отправку сообщений от сообщества текущему пользователю.
* **[messages.denyMessagesFromGroup](/dev/methods/messages/denyMessagesFromGroup)** — запрещает отправку сообщений от сообщества.
* **[messages.isMessagesFromGroupAllowed](/dev/methods/messages/isMessagesFromGroupAllowed)** — проверяет, разрешена ли отправка сообщений от сообщества.

### Папки диалогов и счетчики
* **[messages.createFolder](/dev/methods/messages/createFolder)** — создает папку диалогов.
* **[messages.getFolders](/dev/methods/messages/getFolders)** — возвращает список папок диалогов.
* **[messages.updateFolder](/dev/methods/messages/updateFolder)** — обновляет название и состав папки диалогов.
* **[messages.deleteFolder](/dev/methods/messages/deleteFolder)** — удаляет папку диалогов.
* **[messages.reorderFolders](/dev/methods/messages/reorderFolders)** — изменяет порядок папок.
* **[messages.getCounters](/dev/methods/messages/getCounters)** — возвращает счетчики непрочитанных и неотвеченных сообщений.
