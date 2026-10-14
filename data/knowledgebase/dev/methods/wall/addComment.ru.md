OpenVK-KB-Heading: wall.addComment

# wall.addComment

Добавляет комментарий к записи на стене.

> **Примечание:** Этот метод является удобным псевдонимом для [wall.createComment](/dev/methods/wall/createComment).

### Авторизация
Для вызова этого метода необходим токен пользователя.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца стены. |
| `post_id` | integer | **Обязательный параметр.** Идентификатор записи на стене. |
| `text` | string | Текст комментария (синоним: `message`). |
| `message` | string | Синоним параметра `text`. |
| `reply_to_cid` | integer | Идентификатор комментария, на который оставляется ответ (синоним: `reply_to_comment`). |
| `reply_to_comment` | integer | Синоним параметра `reply_to_cid`. |
| `attachments` | string | Список медиавложений через запятую. |
| `from_group` | integer | `1` — комментарий от имени сообщества. |
| `sticker_id` | integer | Идентификатор прикрепляемого стикера. |

### Результат

Возвращает объект с полем `comment_id` созданного комментария.

### Пример запроса
```http
POST /method/wall.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&post_id=10&text=Отличная+запись!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "comment_id": 26,
        "parents_stack": []
    }
}
```
