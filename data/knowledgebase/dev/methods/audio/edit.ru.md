OpenVK-KB-Heading: audio.edit

# audio.edit

Редактирует метаданные аудиозаписи (имя исполнителя, название, текст песни, жанр и видимость в поиске).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца аудиозаписи. **Обязательный параметр.** |
| `audio_id` | integer | Виртуальный идентификатор аудиозаписи. **Обязательный параметр.** |
| `artist` | string | Новое имя исполнителя / автора. |
| `title` | string | Новое название композиции. |
| `text` | string | Текст песни. |
| `genre_id` | integer | Идентификатор жанра по классификации ВКонтакте (1..22, 1001). |
| `genre_str` | string | Строковое название жанра в OpenVK (например, `Rock`, `Pop`, `Electronic`). |
| `no_search` | integer | `1` — скрыть аудиозапись из глобального поиска, `0` — оставить видимой. По умолчанию: `0`. |

### Результат

Возвращает идентификатор текста песни (`lyrics_id`), если параметр `text` был передан, либо `0`, если текст не изменялся.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `Invalid genre ID {genre_id}` или `Invalid genre_str` — Некорректный жанр. |
| `201` | `Insufficient permissions to edit this audio` — Недостаточно прав для редактирования этой аудиозаписи. |
| `404` | `Not Found` — Аудиозапись не найдена. |

### Пример запроса
```http
POST /method/audio.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&audio_id=45&artist=Rick+Astley&title=Never+Gonna+Give+You+Up+(Remastered)&genre_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 0
}
```
