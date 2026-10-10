OpenVK-KB-Heading: wall.deleteComment

# wall.deleteComment

Удаляет комментарий к записи на стене.

### Авторизация
Для вызова этого метода необходим токен пользователя. Пользователь должен быть автором комментария либо владельцем стены (или администратором сообщества).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `comment_id` | integer | Идентификатор удаляемого комментария (синоним: `cid`). |
| `cid` | integer | Синоним параметра `comment_id`. |
| `owner_id` | integer | Идентификатор владельца стены (необязательный). |

### Результат

Возвращает `1` при успешном удалении комментария.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `7` | `Access denied` — недостаточно прав для удаления комментария. |
| `100` | `One of the parameters specified was missing or invalid` — комментарий не найден. |

### Пример запроса
```http
POST /method/wall.deleteComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

comment_id=26&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
