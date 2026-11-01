OpenVK-KB-Heading: photos.createComment

# photos.createComment

Добавляет новый комментарий к фотографии.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца фотографии (положительное число — ID пользователя, отрицательное — ID сообщества). |
| `photo_id` | integer | **Обязательный параметр.** Идентификатор фотографии. |
| `message` | string | Текст комментария. Обязателен, если не передан `sticker_id` или `attachments`. |
| `from_group` | boolean | `1` — отправить комментарий от имени сообщества, `0` — от своего имени. По умолчанию: `0`. |
| `reply_to_comment` | integer | Идентификатор комментария, на который пишется ответ. |
| `sticker_id` | integer | Идентификатор стикера, который отправляется комментарием. |
| `attachments` | string | Список медиавложений к комментарию. |

### Результат

Возвращает идентификатор (integer) созданного комментария.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Фотография не найдена, удалена или комментирование ограничено настройками приватности. |
| `100` | `Required parameter 'message' missing.` — Не передан текст комментария, вложения или стикер. |
| `100` | `Sticker not found` / `Sticker is not available for you` — Стикер не найден или недоступен пользователю. |

### Пример запроса
```http
POST /method/photos.createComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&message=Отличный%20снимок!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 15
}
```
