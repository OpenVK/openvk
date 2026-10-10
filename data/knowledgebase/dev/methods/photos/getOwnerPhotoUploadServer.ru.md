OpenVK-KB-Heading: photos.getOwnerPhotoUploadServer

# photos.getOwnerPhotoUploadServer

Возвращает адрес сервера для загрузки главной фотографии (аватара) пользователя или сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца аватара (положительное число или `0` — текущий пользователь, отрицательное — идентификатор сообщества). По умолчанию: `0`. |

### Результат

Возвращает объект с полем `upload_url`:
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/hash...?info..."
    }
}
```

> **Примечание:** После выполнения POST-запроса на полученный адрес сервер возвратит ответ с полями `photo` и `hash`, которые необходимо передать в метод [photos.saveOwnerPhoto](/dev/methods/photos/saveOwnerPhoto).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `200` | `Access: Club can't be 'written' by user` — Нет прав администратора на смену аватара сообщества. |
| `404` | `Club not found` — Указанное сообщество не найдено. |

### Пример запроса
```http
POST /method/photos.getOwnerPhotoUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=-1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/a1b2c3d4e5f6?info..."
    }
}
```
