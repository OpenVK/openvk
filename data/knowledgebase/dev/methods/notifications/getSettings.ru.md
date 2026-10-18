OpenVK-KB-Heading: notifications.getSettings

# notifications.getSettings

Возвращает настройки уведомлений текущего пользователя.

> **Примечание (заглушка совместимости):** В текущей версии OpenVK метод является заглушкой для совместимости с мобильными клиентами и возвращает пустые списки настроек.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `device_id` | string | Идентификатор устройства. |
| `from` | string | Источник вызова. |
| `lang` | string | Языковой код интерфейса. |

### Результат

Возвращает объект с коллекциями настроек:
* `apps` (array) — пустой массив `[]`;
* `groups` (array) — пустой массив `[]`;
* `photos` (array) — пустой массив `[]`;
* `profiles` (array) — пустой массив `[]`;
* `items` (object) — пустой объект `{}`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/notifications.getSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "apps": [],
        "groups": [],
        "photos": [],
        "profiles": [],
        "items": {}
    }
}
```
