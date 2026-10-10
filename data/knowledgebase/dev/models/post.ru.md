OpenVK-KB-Heading: Объект Post

# Объект Post

Объект **Post** описывает запись на стене пользователя или сообщества ВКонтакте / OpenVK. Структура возвращаемых полей зависит от версии API, переданной в параметре `v`.

---

## Версия API 5.0 и выше (v >= 5.0)

В версиях API 5.0+ используются следующие поля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор записи на стене. |
| `owner_id` | integer | Идентификатор владельца стены (положительное число для пользователя, отрицательное для сообщества). |
| `from_id` | integer | Идентификатор автора, опубликовавшего запись. |
| `date` | integer | Время публикации записи (Unix timestamp). |
| `text` | string | Текст записи. |
| `reply_owner_id` | integer | *(Опционально)* Идентификатор владельца записи, в ответ на которую оставлена текущая. |
| `reply_post_id` | integer | *(Опционально)* Идентификатор записи, в ответ на которую оставлена текущая. |
| `friends_only` | integer | `1`, если запись видна только друзьям. |
| `comments` | object | Информация о комментариях к записи (`count`, `can_post`). |
| `likes` | object | Информация о лайках (`count`, `user_likes`, `can_like`, `can_publish`). |
| `reposts` | object | Информация о репостах (`count`, `user_reposted`). |
| `views` | object | Информация о просмотрах (`count`). |
| `post_type` | string | Тип записи: `"post"`, `"copy"`, `"reply"`, `"postpone"`, `"suggest"`. |
| `attachments` | array | Список медиа-вложений (фотографии, аудио, видео, документы, опросы и др.). |
| `is_pinned` | integer | `1`, если запись закреплена. |
| `copy_history` | array | *(Опционально)* Массив объектов записей-источников, если текущая запись является репостом. |

### Пример объекта (v >= 5.0)
```json
{
    "id": 42,
    "owner_id": 1,
    "from_id": 1,
    "date": 1696680000,
    "text": "Добро пожаловать в OpenVK!",
    "post_type": "post",
    "comments": {
        "count": 5,
        "can_post": 1
    },
    "likes": {
        "count": 12,
        "user_likes": 0,
        "can_like": 1
    },
    "reposts": {
        "count": 3,
        "user_reposted": 0
    },
    "attachments": []
}
```

---

## До версии API 5.0 (v < 5.0)

В версиях API 3.x и 4.x используются устаревшие поля `to_id`, `media`, `attachment`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор записи на стене. |
| `to_id` | integer | Идентификатор владельца стены (эквивалент `owner_id` в API v5.0+). |
| `from_id` | integer | Идентификатор автора записи. |
| `date` | integer | Время публикации (Unix timestamp). |
| `text` | string | Текст записи. |
| `comments` | object | Информация о комментариях (`count`, `can_post`). |
| `likes` | object | Информация об отметках Мне нравится (`count`, `user_likes`). |
| `reposts` | object | Информация о репостах (`count`). |
| `attachment` | object | *(Опционально)* Одиночное вложение в старом формате. |
| `attachments` | array | *(Опционально)* Список вложений. |
| `copy_owner_id` | integer | Идентификатор владельца репостнутой записи. |
| `copy_post_id` | integer | Идентификатор репостнутой записи. |

### Пример объекта (v < 5.0)
```json
{
    "id": 42,
    "to_id": 1,
    "from_id": 1,
    "date": 1696680000,
    "text": "Добро пожаловать в OpenVK!",
    "comments": {
        "count": 5
    },
    "likes": {
        "count": 12,
        "user_likes": 0
    },
    "reposts": {
        "count": 3
    }
}
```
