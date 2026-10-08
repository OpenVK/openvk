OpenVK-KB-Heading: Методы wall

# Методы wall

Секция **wall** содержит методы для работы со стеной пользователей и сообществ: получение записей, публикация, репосты, комментирование, закрепление, архивация и геопоиск записей.

## Список методов

* **[wall.get](/dev/methods/wall/get)** — возвращает список записей со стены пользователя или сообщества.
* **[wall.getArchiveYears](/dev/methods/wall/getArchiveYears)** — возвращает список годов, за которые есть архивные записи.
* **[wall.getById](/dev/methods/wall/getById)** — возвращает информацию о записях по их идентификаторам.
* **[wall.post](/dev/methods/wall/post)** — публикует новую запись на стене пользователя или сообщества.
* **[wall.repost](/dev/methods/wall/repost)** — копирует объект (запись, фото, видео) на стену пользователя или сообщества.
* **[wall.getComments](/dev/methods/wall/getComments)** — возвращает список комментариев к записи на стене.
* **[wall.getComment](/dev/methods/wall/getComment)** — возвращает подробную информацию о конкретном комментарии.
* **[wall.createComment](/dev/methods/wall/createComment)** — создает комментарий к записи на стене.
* **[wall.addComment](/dev/methods/wall/addComment)** — добавляет комментарий к записи (псевдоним wall.createComment).
* **[wall.deleteComment](/dev/methods/wall/deleteComment)** — удаляет комментарий к записи.
* **[wall.delete](/dev/methods/wall/delete)** — удаляет запись со стены.
* **[wall.edit](/dev/methods/wall/edit)** — редактирует запись на стене.
* **[wall.editComment](/dev/methods/wall/editComment)** — редактирует комментарий к записи.
* **[wall.checkCopyrightLink](/dev/methods/wall/checkCopyrightLink)** — проверяет корректность внешней ссылки источника (копирайта).
* **[wall.pin](/dev/methods/wall/pin)** — закрепляет запись на стене.
* **[wall.unpin](/dev/methods/wall/unpin)** — открепляет запись на стене.
* **[wall.getNearby](/dev/methods/wall/getNearby)** — возвращает записи с географическими координатами поблизости от указанной записи.
* **[wall.archive](/dev/methods/wall/archive)** — отправляет запись в архив.
* **[wall.reveal](/dev/methods/wall/reveal)** — восстанавливает запись из архива на стену.
* **[wall.getSubscriptions](/dev/methods/wall/getSubscriptions)** — возвращает список подписок на обновления стены.
