OpenVK-KB-Heading: audio.isLagtrain

# audio.isLagtrain

Специальный метод API OpenVK (easter egg), проверяющий, содержит ли название аудиозаписи композицию «Lagtrain».

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `audio_id` | string | Идентификатор аудиозаписи (в формате `owner_id_vid`, `id` или `unique_id`). **Обязательный параметр.** |

### Результат

Возвращает `1`, если в названии трека содержится подстрока `Lagtrain`, либо `0` в противном случае.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `Invalid audio {id}` — Некорректный синтаксис идентификатора. |
| `404` | `Audio not found` — Аудиозапись не найдена. |

### Пример запроса
```http
POST /method/audio.isLagtrain HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio_id=1_42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
