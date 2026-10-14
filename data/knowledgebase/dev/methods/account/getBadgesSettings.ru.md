OpenVK-KB-Heading: account.getBadgesSettings

# account.getBadgesSettings

Возвращает параметры отображения бейджей и значков уведомлений.

> **Примечание (заглушка совместимости):** Система значков (badges) в OpenVK отключена. Метод возвращает `{"items": [], "is_enabled": false}`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
Возвращает объект параметров бейджей:

| Поле | Тип | Описание |
| --- | --- | --- |
| `items` | array | Список элементов бейджей (`[]`). |
| `is_enabled` | boolean | Включены ли бейджи (`false`). |

### Пример запроса
```http
POST /method/account.getBadgesSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [],
        "is_enabled": false
    }
}
```
