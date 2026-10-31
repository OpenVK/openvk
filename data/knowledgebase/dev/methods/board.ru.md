OpenVK-KB-Heading: Методы секции board

# Методы секции board

Секция **board** содержит методы для работы с обсуждениями (темами) в сообществах, создания комментариев, закрепления, закрытия и привязки чатов к обсуждениям.

### Обсуждения (темы)
* **[board.getTopics](/dev/methods/board/getTopics)** — возвращает список обсуждений сообщества с поддержкой предпросмотра первого и последнего комментария.
* **[board.addTopic](/dev/methods/board/addTopic)** — создает новую тему в обсуждениях сообщества.
* **[board.addChatTopic](/dev/methods/board/addChatTopic)** — создает тему в обсуждениях, привязанную к групповой беседе (чату).
* **[board.editTopic](/dev/methods/board/editTopic)** — редактирует заголовок обсуждения.
* **[board.closeTopic](/dev/methods/board/closeTopic)** — закрывает обсуждение для добавления новых сообщений.
* **[board.openTopic](/dev/methods/board/openTopic)** — открывает ранее закрытое обсуждение.
* **[board.fixTopic](/dev/methods/board/fixTopic)** — закрепляет обсуждение в начале списка тем.
* **[board.unfixTopic](/dev/methods/board/unfixTopic)** — открепляет обсуждение.
* **[board.deleteTopic](/dev/methods/board/deleteTopic)** — удаляет обсуждение.

### Комментарии в обсуждениях
* **[board.getComments](/dev/methods/board/getComments)** — возвращает список комментариев в теме обсуждения.
* **[board.createComment](/dev/methods/board/createComment)** — добавляет новый комментарий (сообщение или стикер) в обсуждение.
