OpenVK-KB-Heading: Методы photos

# Методы photos

Секция **photos** содержит методы для работы с фотографиями, альбомами, комментариями к ним, а также для получения адресов серверов загрузки изображений в различные разделы сайта (профиль, стену, сообщения, альбомы).

## Список методов

### Альбомы
* **[photos.createAlbum](/dev/methods/photos/createAlbum)** — создает новый пустой фотоальбом.
* **[photos.editAlbum](/dev/methods/photos/editAlbum)** — редактирует название и описание альбома.
* **[photos.getAlbums](/dev/methods/photos/getAlbums)** — возвращает список фотоальбомов пользователя или сообщества.
* **[photos.getAlbumsCount](/dev/methods/photos/getAlbumsCount)** — возвращает количество альбомов пользователя или сообщества.
* **[photos.deleteAlbum](/dev/methods/photos/deleteAlbum)** — удаляет фотоальбом.

### Фотографии
* **[photos.get](/dev/methods/photos/get)** — возвращает список фотографий из альбома или по списку идентификаторов.
* **[photos.getById](/dev/methods/photos/getById)** — возвращает информацию о фотографиях по их идентификаторам.
* **[photos.getAll](/dev/methods/photos/getAll)** — возвращает все фотографии пользователя или сообщества в обратном хронологическом порядке.
* **[photos.getUserPhotos](/dev/methods/photos/getUserPhotos)** — возвращает все фотографии пользователя (псевдоним для `photos.getAll`).
* **[photos.edit](/dev/methods/photos/edit)** — изменяет описание (подпись) фотографии.
* **[photos.delete](/dev/methods/photos/delete)** — удаляет одну или несколько фотографий.

### Загрузка фотографий
* **[photos.getUploadServer](/dev/methods/photos/getUploadServer)** — возвращает адрес сервера для загрузки фотографий в альбом.
* **[photos.save](/dev/methods/photos/save)** — сохраняет фотографии после успешной загрузки в альбом.
* **[photos.getOwnerPhotoUploadServer](/dev/methods/photos/getOwnerPhotoUploadServer)** — возвращает адрес сервера для загрузки главной фотографии профиля или сообщества.
* **[photos.saveOwnerPhoto](/dev/methods/photos/saveOwnerPhoto)** — сохраняет главную фотографию профиля или сообщества после загрузки.
* **[photos.getWallUploadServer](/dev/methods/photos/getWallUploadServer)** — возвращает адрес сервера для загрузки фотографии на стену.
* **[photos.saveWallPhoto](/dev/methods/photos/saveWallPhoto)** — сохраняет фотографию для публикации на стене.
* **[photos.getMessagesUploadServer](/dev/methods/photos/getMessagesUploadServer)** — возвращает адрес сервера для загрузки фотографии в личное сообщение.
* **[photos.saveMessagesPhoto](/dev/methods/photos/saveMessagesPhoto)** — сохраняет фотографию для отправки в сообщении.
* **[photos.getChatUploadServer](/dev/methods/photos/getChatUploadServer)** — возвращает адрес сервера для загрузки главной фотографии беседы.

### Комментарии
* **[photos.getComments](/dev/methods/photos/getComments)** — возвращает список комментариев к фотографии.
* **[photos.createComment](/dev/methods/photos/createComment)** — добавляет новый комментарий к фотографии.
* **[photos.addComment](/dev/methods/photos/addComment)** — добавляет комментарий к фотографии (псевдоним для `photos.createComment`).

### Отметки на фото
* **[photos.getTags](/dev/methods/photos/getTags)** — возвращает список отметок на фотографии.
* **[photos.putTag](/dev/methods/photos/putTag)** — добавляет отметку пользователя на фотографии.
* **[photos.deleteTag](/dev/methods/photos/deleteTag)** — удаляет отметку с фотографии.
* **[photos.confirmTag](/dev/methods/photos/confirmTag)** — подтверждает отметку на фотографии.
