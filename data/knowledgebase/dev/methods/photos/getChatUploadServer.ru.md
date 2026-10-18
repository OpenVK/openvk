OpenVK-KB-Heading: photos.getChatUploadServer

# photos.getChatUploadServer

Возвращает адрес сервера для загрузки главной фотографии (обложки) групповой беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos` или `messages`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | **Обязательный параметр.** Идентификатор групповой беседы (числовой ID без смещения 2000000000). |
| `group_id` | integer | Идентификатор сообщества (если чат принадлежит сообществу). По умолчанию: `0`. |

### Результат

Возвращает объект с полем `upload_url`:
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/chat_hash...?info..."
    }
}
```

> **Примечание:** После отправки POST-запроса на `upload_url` сервер возвращает ответ, содержащий поле `response` с метаданными загруженной обложки чата, которые затем используются методом [messages.setChatPhoto](/dev/methods/messages/setChatPhoto).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `Invalid chat_id` — Передан некорректный идентификатор беседы (`chat_id <= 0`). |

### Пример запроса
```http
POST /method/photos.getChatUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/chat_cover123?info..."
    }
}
```
