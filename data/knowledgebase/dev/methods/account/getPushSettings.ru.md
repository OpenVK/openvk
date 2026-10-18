OpenVK-KB-Heading: account.getPushSettings

# account.getPushSettings

Возвращает текущие настройки Push-уведомлений для устройства или беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `token` | string | Токен устройства. |
| `peer_id` | integer | Идентификатор диалога/беседы. Если не указан — возвращаются глобальные настройки. |

### Результат
Возвращает объект, содержащий параметры уведомлений:

| Поле | Тип | Описание |
| --- | --- | --- |
| `disabled_until` | integer | Время Unix timestamp, до которого отключены уведомления (`-1` — отключены навсегда, `0` — уведомления включены). |
| `sound` | integer | `1` если звук включен, `0` если выключен. |

### Пример запроса
```http
POST /method/account.getPushSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

token=DEVICE_PUSH_TOKEN_HERE&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "disabled_until": 0,
        "sound": 1
    }
}
```
