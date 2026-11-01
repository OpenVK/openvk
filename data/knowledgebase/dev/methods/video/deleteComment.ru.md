OpenVK-KB-Heading: video.deleteComment

# video.deleteComment

Удаляет комментарий к видеозаписи.

### Авторизация
Для вызова этого метода необходим токен пользователя. Пользователь должен быть автором комментария либо владельцем видеозаписи (или администратором сообщества).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `comment_id` | integer | Идентификатор удаляемого комментария (синоним: `cid`). |
| `cid` | integer | Синоним параметра `comment_id`. |
| `video_id` | integer | Идентификатор видеозаписи (необязательный). |
| `owner_id` | integer | Идентификатор владельца видеозаписи (необязательный). |

### Результат

Возвращает `1` при успешном выполнении.

### Пример запроса
```http
POST /method/video.deleteComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

comment_id=16&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
