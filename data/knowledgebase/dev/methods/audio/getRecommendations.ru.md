OpenVK-KB-Heading: audio.getRecommendations

# audio.getRecommendations

Возвращает список рекомендованных аудиозаписей для текущего пользователя.

> **Примечание:** В текущей версии OpenVK метод является заглушкой совместимости и возвращает пустой список.

### Авторизация
Для вызова этого метода не требуется обязательная авторизация.

### Параметры
Параметры отсутствуют.

### Результат

В API версии 5.0 и выше возвращает объект с пустым списком:
```json
{
    "count": 0,
    "items": []
}
```

В API версии ниже 5.0 возвращает массив `[0]`.

### Пример запроса
```http
POST /method/audio.getRecommendations HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
