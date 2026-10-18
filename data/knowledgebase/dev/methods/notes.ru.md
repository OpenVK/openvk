OpenVK-KB-Heading: Методы notes

# Методы notes

Секция **notes** содержит методы для работы с заметками пользователей: создание, редактирование, удаление, получение заметок и комментариев к ним.

## Список методов

* **[notes.add](/dev/methods/notes/add)** — создает новую заметку у текущего пользователя.
* **[notes.edit](/dev/methods/notes/edit)** — редактирует существующую заметку текущего пользователя.
* **[notes.delete](/dev/methods/notes/delete)** — удаляет заметку текущего пользователя.
* **[notes.get](/dev/methods/notes/get)** — возвращает список заметок указанного пользователя.
* **[notes.getById](/dev/methods/notes/getById)** — возвращает подробную информацию о заметке по её идентификатору.
* **[notes.getComments](/dev/methods/notes/getComments)** — возвращает список комментариев к заметке.
* **[notes.createComment](/dev/methods/notes/createComment)** — добавляет новый комментарий к заметке.
* **[notes.addComment](/dev/methods/notes/addComment)** — добавляет комментарий к заметке (псевдоним для `notes.createComment`).
