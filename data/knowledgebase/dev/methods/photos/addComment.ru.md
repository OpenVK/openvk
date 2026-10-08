OpenVK-KB-Heading: photos.addComment

# photos.addComment

Добавляет комментарий к фотографии. Метод предоставлен для совместимости со старыми версиями API и клиентами ВКонтакте (аналог [photos.createComment](/dev/methods/photos/createComment)).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца фотографии (положительное число — ID пользователя, отрицательное — ID сообщества). |
| `photo_id` | integer | Идентификатор фотографии. |
| `pid` | integer | Альтернативный идентификатор фотографии (для совместимости). |
| `message` | string | Текст комментария. |
| `text` | string | Альтернативный параметр для текста комментария. |
| `reply_to_comment` | integer | Идентификатор комментария, на который пишется ответ. |
| `reply_to_cid` | integer | Альтернативный параметр ID родительского комментария. |
| `attachments` | string | Медиавложения к комментарию. |
| `from_group` | integer | `1` — отправить от имени группы, `0` — от имени пользователя. |

### Результат

Возвращает идентификатор (integer) созданного комментария.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к комментированию фотографии ограничен. |

### Пример запроса
```http
POST /method/photos.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&pid=1&message=Прекрасное%20фото&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 16
}
```
