OpenVK-KB-Heading: polls.deleteVote

# polls.deleteVote

Отзывает голос текущего пользователя в опросе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `wall`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `poll_id` | integer | **Обязательный параметр.** Идентификатор опроса. |
| `owner_id` | integer | Идентификатор владельца опроса. По умолчанию: `0`. |
| `answer_id` | integer | Идентификатор варианта ответа (параметр совместимости). |

### Результат

Возвращает `1` в случае успешного отзыва голоса.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied: Poll is locked or isn't revotable` — Опрос завершен, заблокирован или в настройках опроса запрещен отзыв голоса. |
| `251` | `Invalid poll id` — Опрос не найден. |

### Пример запроса
```http
POST /method/polls.deleteVote HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
