OpenVK-KB-Heading: friends.getMutual

# friends.getMutual

Возвращает список общих друзей между текущим (или указанным) пользователем и другими пользователями.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `source_uid` | integer | Идентификатор пользователя, чьи общие друзья ищутся. Если не указан, используется ID текущего пользователя. По умолчанию: `0`. |
| `target_uid` | integer | Идентификатор целевого пользователя, с которым ищутся общие друзья (используется, если не передан `target_uids`). |
| `target_uids` | string | Список идентификаторов пользователей через запятую, с которыми необходимо найти общих друзей (например, `2,3,4`). |
| `order` | string | Порядок сортировки результатов: `"random"` — в случайном порядке, иначе — по возрастанию идентификаторов. По умолчанию: пустая строка. |
| `count` | integer | Количество возвращаемых общих друзей. По умолчанию: все. |
| `offset` | integer | Смещение относительно начала списка общих друзей. По умолчанию: `0`. |
| `need_common_count` | boolean | `1` — возвращать общее количество общих друзей в поле `common_count`, `0` — не возвращать. По умолчанию: `0`. |

### Результат

* Если передан одиночный `target_uid`, возвращается объект с полями:
  * `target_uid` (integer) — идентификатор целевого пользователя;
  * `common_friends` (array) — массив числовых ID общих друзей;
  * `common_count` (integer, опционально) — общее число общих друзей (при `need_common_count=1`).
* Если передан список `target_uids`, возвращается массив таких объектов для каждого целевого пользователя.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `30` | `This profile is private` — Список друзей целевого пользователя скрыт настройками приватности. |
| `100` | `User was deleted or banned` — Исходный пользователь удален или заблокирован. |
| `100` | `One of the parameters specified was missing or invalid: target_uid is undefined` — Не указан идентификатор целевого пользователя (`target_uid` или `target_uids`). |
| `100` | `One of the parameters specified was missing or invalid: target_uids[N] not integer` — В списке `target_uids` передан некорректный идентификатор. |

### Пример запроса
```http
POST /method/friends.getMutual HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

target_uid=2&need_common_count=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "target_uid": 2,
        "common_friends": [
            3,
            7,
            12
        ],
        "common_count": 3
    }
}
```
