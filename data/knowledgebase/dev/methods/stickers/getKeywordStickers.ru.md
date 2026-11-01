OpenVK-KB-Heading: stickers.getKeywordStickers

# stickers.getKeywordStickers

Возвращает список стикеров, соответствующих заданным ключевым словам.

> **Примечание:** В OpenVK метод является заглушкой для совместимости и возвращает пустой массив. Для получения подсказок стикеров рекомендуется использовать метод [stickers.getStickersKeywords](/dev/methods/stickers/getStickersKeywords).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `words` | string | Ключевые слова через запятую. |
| `need_stickers` | integer | `1` — возвращать объекты стикеров, `0` — нет. По умолчанию: `1`. |
| `aliases` | string | Алиасы ключевых слов. |

### Результат

Возвращает пустой массив `[]`.

### Пример запроса
```http
POST /method/stickers.getKeywordStickers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

words=привет&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": []
}
```
