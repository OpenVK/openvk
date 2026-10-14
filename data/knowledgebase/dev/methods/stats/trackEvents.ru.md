OpenVK-KB-Heading: stats.trackEvents

# stats.trackEvents

Передает массив аналитических событий от клиентских приложений и игр.

> **Примечание:** В OpenVK метод является заглушкой для совместимости с внешними клиентами и всегда возвращает `1`.

### Авторизация
Этот метод не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `events` | string | JSON-строка или массив объектов событий для аналитики. |

### Результат

Возвращает `1`.

### Пример запроса
```http
POST /method/stats.trackEvents HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

events=[{"event_type":"app_open"}]&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
