OpenVK-KB-Heading: audio.subscribeToQueue

# audio.subscribeToQueue

Возвращает URL очереди воспроизведения аудиозаписей для получения событий в реальном времени.

> **Примечание:** В текущей реализации метода возвращается объект-заглушка с пустым URL.

### Авторизация
Для вызова этого метода не требуется обязательная авторизация.

### Параметры
Параметры отсутствуют.

### Результат

Возвращает объект с полем:
* `url` (string) — URL сервера очередей (в текущей реализации пустая строка `""`).

### Пример запроса
```http
POST /method/audio.subscribeToQueue HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "url": ""
    }
}
```
