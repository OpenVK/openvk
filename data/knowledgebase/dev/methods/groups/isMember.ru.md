OpenVK-KB-Heading: groups.isMember

# groups.isMember

Проверяет, является ли пользователь участником (подписчиком) указанного сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | **Обязательный параметр**. Идентификатор сообщества. |
| `user_id` | integer | Идентификатор проверяемого пользователя. Если не указан или равен `0`, проверяется текущий пользователь. По умолчанию: `0`. |
| `extended` | integer | `1` — возвращать расширенную информацию о статусе участия, `0` — возвращать только числовой статус (`1` или `0`). По умолчанию: `0`. |

### Результат

* При `extended=0` возвращает `1`, если пользователь является участником, или `0`, если не является.
* При `extended=1` возвращает объект:
  * `member` (integer) — `1`, если пользователь состоит в сообществе, иначе `0`;
  * `request` (integer) — `0`;
  * `invitation` (integer) — `0`;
  * `can_invite` (integer) — `0`;
  * `can_recall` (integer) — `0`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к просмотру сообщества ограничен. |
| `15` | `Not found` — Проверяемый пользователь не найден или удален. |

### Пример запроса
```http
POST /method/groups.isMember HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&user_id=2&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "member": 1,
        "request": 0,
        "invitation": 0,
        "can_invite": 0,
        "can_recall": 0
    }
}
```
