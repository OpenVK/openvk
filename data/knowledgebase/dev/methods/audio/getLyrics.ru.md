OpenVK-KB-Heading: audio.getLyrics

# audio.getLyrics

Возвращает текст песни по идентификатору аудиозаписи.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `lyrics_id` | integer | Идентификатор текста песни (совпадает с глобальным идентификатором аудиозаписи). **Обязательный параметр.** |

### Результат

Возвращает объект с полями:
* `lyrics_id` (integer) — идентификатор текста песни;
* `text` (string) — текст песни с нормализованными переносами строк (`\n`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `201` | `Access denied to lyrics` — Доступ к аудиозаписи ограничен настройками приватности. |
| `404` | `Not found` — Аудиозапись не найдена или у нее отсутствует текст песни. |

### Пример запроса
```http
POST /method/audio.getLyrics HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

lyrics_id=105&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "lyrics_id": 105,
        "text": "We're no strangers to love\nYou know the rules and so do I\nA full commitment's what I'm thinking of\nYou wouldn't get this from any other guy\n\nI just wanna tell you how I'm feeling\nGotta make you understand\n\nNever gonna give you up\nNever gonna let you down\nNever gonna run around and desert you"
    }
}
```
