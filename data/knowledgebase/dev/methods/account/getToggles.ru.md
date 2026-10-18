OpenVK-KB-Heading: account.getToggles

# account.getToggles

Возвращает состояние серверных переключателей возможностей (feature toggles) и A/B-экспериментов для клиентских приложений.

> **Примечание (заглушка совместимости):** Возвращает фиксированный список отключенных возможностей (`core_common_websocket`, `core_common_websocket_api`, `core_common_websocket_compress`, `core_common_websocket_rate_lmt`, `queue_new_subscribe`) для предотвращения несовместимых попыток подключения официальных клиентов через нереализованные протоколы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
Возвращает объект, содержащий переключатели и эксперименты:

| Поле | Тип | Описание |
| --- | --- | --- |
| `toggles` | array | Массив объектов переключателей функций (`name`, `enabled`, `value`). |
| `version` | integer | Версия схемы переключателей (`1`). |
| `ab_tests` | array | Список активных A/B экспериментов (`[]`). |

### Пример запроса
```http
POST /method/account.getToggles HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "toggles": [
            {
                "name": "core_common_websocket",
                "enabled": false,
                "value": null
            },
            {
                "name": "core_common_websocket_api",
                "enabled": false,
                "value": null
            },
            {
                "name": "core_common_websocket_compress",
                "enabled": false,
                "value": null
            },
            {
                "name": "core_common_websocket_rate_lmt",
                "enabled": false,
                "value": null
            },
            {
                "name": "queue_new_subscribe",
                "enabled": false,
                "value": null
            }
        ],
        "version": 1,
        "ab_tests": []
    }
}
```
