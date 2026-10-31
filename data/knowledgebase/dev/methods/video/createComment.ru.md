OpenVK-KB-Heading: video.createComment

# video.createComment

Создает новый комментарий к видеозаписи.

### Авторизация
Для вызова этого метода необходим токен пользователя с правами на отправку сообщений и комментариев.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `video_id` | integer | **Обязательный параметр.** Идентификатор видеозаписи. |
| `owner_id` | integer | Идентификатор владельца видеозаписи (положительное число — пользователь, отрицательное — сообщество). По умолчанию: ID текущего пользователя. |
| `message` | string | Текст комментария (синоним: `text`). Обязателен, если не передан `sticker_id` или `attachments`. |
| `text` | string | Синоним параметра `message`. |
| `reply_to_cid` | integer | Идентификатор комментария, ответом на который является создаваемый комментарий (синоним: `reply_to_comment`). |
| `reply_to_comment` | integer | Синоним параметра `reply_to_cid`. |
| `sticker_id` | integer | Идентификатор стикера, который необходимо прикрепить к комментарию. |
| `attachments` | string | Список медиавложений через запятую (например, `photo1_23`). |

### Результат

Возвращает объект с идентификатором созданного комментария:

| Поле | Тип | Описание |
| --- | --- | --- |
| `comment_id` | integer | Идентификатор созданного комментария (в API версии 5.x). |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: video not found` — видеозапись не найдена или удалена. |
| `100` | `Required parameter 'message' is missing` — не передан текст комментария, стикер или вложение. |
| `100` | `Sticker not found` / `Sticker is not available for you` — указанный стикер не найден или недоступен пользователю. |

### Пример запроса
```http
POST /method/video.createComment HTTP/1.1
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
