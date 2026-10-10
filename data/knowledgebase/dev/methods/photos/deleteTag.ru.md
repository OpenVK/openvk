OpenVK-KB-Heading: photos.deleteTag

# photos.deleteTag

Удаляет отметку с фотографии.

> **Примечание:** В OpenVK метод является заглушкой для совместимости с клиентами и всегда возвращает `1`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца фотографии. |
| `tag_id` | integer | **Обязательный параметр.** Идентификатор удаляемой отметки. |
| `photo_id` | integer | Идентификатор фотографии. |
| `pid` | integer | Альтернативный идентификатор фотографии. |

### Результат

Возвращает `1`.

### Пример запроса
```http
POST /method/photos.deleteTag HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&tag_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
