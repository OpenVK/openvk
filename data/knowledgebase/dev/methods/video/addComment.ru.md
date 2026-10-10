OpenVK-KB-Heading: video.addComment

# video.addComment

Добавляет новый комментарий к видеозаписи.

> **Примечание:** Этот метод является полным псевдонимом метода [video.createComment](/dev/methods/video/createComment).

### Авторизация
Для вызова этого метода необходим токен пользователя с правами на добавление комментариев.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `video_id` | integer | **Обязательный параметр.** Идентификатор видеозаписи. |
| `owner_id` | integer | Идентификатор владельца видеозаписи (положительное число — пользователь, отрицательное — сообщество). По умолчанию: ID текущего пользователя. |
| `message` | string | Текст комментария (синоним: `text`). Обязателен, если не передан `sticker_id` или `attachments`. |
| `text` | string | Синоним параметра `message`. |
| `reply_to_cid` | integer | Идентификатор комментария, ответом на который является создаваемый комментарий (синоним: `reply_to_comment`). |
| `reply_to_comment` | integer | Синоним параметра `reply_to_cid`. |
| `sticker_id` | integer | Идентификатор прикрепляемого стикера. |
| `attachments` | string | Список медиавложений через запятую. |

### Результат

Возвращает объект с идентификатором созданного комментария:

| Поле | Тип | Описание |
| --- | --- | --- |
| `comment_id` | integer | Идентификатор созданного комментария. |

### Пример запроса
```http
POST /method/video.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&video_id=1&message=Отличное+видео!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "comment_id": 16
    }
}
```
