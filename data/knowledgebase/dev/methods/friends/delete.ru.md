OpenVK-KB-Heading: friends.delete

# friends.delete

Удаляет пользователя из списка друзей (переводя его в подписчики).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `friends`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | **Обязательный параметр**. Идентификатор пользователя, которого необходимо удалить из друзей. |

### Результат

В API версии 5.0 и выше возвращает число `1` в случае успешного удаления.

В API версии ниже 5.0 возвращает объект:
```json
{
    "success": 1
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied: No friend or friend request found.` — Указанный пользователь не является другом. |
| `100` | `Invalid user` — Пользователь с указанным идентификатором не найден. |

### Пример запроса
```http
POST /method/friends.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
