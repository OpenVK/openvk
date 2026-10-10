OpenVK-KB-Heading: photos.getMessagesUploadServer

# photos.getMessagesUploadServer

Возвращает адрес сервера для загрузки фотографии в личное сообщение или групповую беседу.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos` или `messages`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор диалога или беседы, в которую планируется отправить фото. По умолчанию: `0`. |
| `group_id` | integer | Идентификатор сообщества (если загрузка осуществляется от имени группы). По умолчанию: `0`. |

### Результат

Возвращает объект с полем `upload_url`:
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/msg_hash...?info..."
    }
}
```

> **Примечание:** После выполнения POST-запроса с изображением (поле `photo`) на полученный адрес сервер вернет параметры `photo`, `server` и `hash`, которые необходимо передать в метод [photos.saveMessagesPhoto](/dev/methods/photos/saveMessagesPhoto).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/photos.getMessagesUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/msg_hash123?info..."
    }
}
```
