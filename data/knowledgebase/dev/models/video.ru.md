OpenVK-KB-Heading: Объект Video

# Объект Video

Объект **Video** описывает видеозапись ВКонтакте / OpenVK. Структура возвращаемых полей зависит от версии API, переданной в параметре `v`.

---

## Версия API 5.0 и выше (v >= 5.0)

В версиях API 5.0+ используются следующие поля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор видеозаписи. |
| `owner_id` | integer | Идентификатор владельца видеозаписи (пользователя или сообщества). |
| `title` | string | Название видеозаписи. |
| `description` | string | Текстовое описание видеозаписи. |
| `duration` | integer | Длительность видео в секундах. |
| `date` | integer | Дата добавления видео (Unix timestamp). |
| `views` | integer | Количество просмотров видеозаписи. |
| `comments` | integer | Количество комментариев к видеозаписи. |
| `player` | string | URL страницы со встроенным видеоплеером (iframe). |
| `platform` | string | *(Опционально)* Платформа внешнего видео (например `"youtube"`). |
| `access_key` | string | Ключ доступа к видеозаписи. |
| `image` | array | Массив объектов с обложками видео разных размеров (`url`, `width`, `height`). |
| `files` | object | Объект с прямыми ссылками на видеофайлы различных качеств (`mp4_240`, `mp4_360`, `mp4_480`, `mp4_720`, `mp4_1080`). |
| `likes` | object | *(Опционально)* Информация о лайках (`count`, `user_likes`). |
| `reposts` | object | Информация о репостах (`count`, `user_reposted`). |
| `can_comment` | integer | `1`, если текущий пользователь может оставлять комментарии. |
| `can_like` | integer | `1`, если текущий пользователь может ставить отметку Мне нравится. |
| `can_repost` | integer | `1`, если разрешен репост видео. |

### Пример объекта (v >= 5.0)
```json
{
    "id": 84,
    "owner_id": 1,
    "title": "Демонстрация OpenVK",
    "description": "Обзор основных возможностей движка",
    "duration": 120,
    "date": 1696680000,
    "views": 450,
    "comments": 12,
    "player": "https://openvk.instance/video_ext.php?oid=1&id=84&hash=abc123",
    "access_key": "abc123def",
    "image": [
        {
            "url": "https://openvk.instance/videos/thumbs/84.jpg",
            "width": 320,
            "height": 240
        }
    ],
    "likes": {
        "count": 25,
        "user_likes": 1
    }
}
```

---

## До версии API 5.0 (v < 5.0)

В версиях API 3.x и 4.x возвращаются устаревшие поля `vid`, `image` и `image_medium`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `vid` | integer | Идентификатор видеозаписи (эквивалент `id` в API v5.0+). |
| `owner_id` | integer | Идентификатор владельца видеозаписи. |
| `title` | string | Название видеозаписи. |
| `description` | string | Описание видеозаписи. |
| `duration` | integer | Длительность в секундах. |
| `date` | integer | Дата добавления (Unix timestamp). |
| `views` | integer | Количество просмотров. |
| `image` | string | URL обложки видео размером 130x100px. |
| `image_medium` | string | URL обложки видео размером 320x240px. |
| `player` | string | URL страницы видеоплеера. |

### Пример объекта (v < 5.0)
```json
{
    "vid": 84,
    "owner_id": 1,
    "title": "Демонстрация OpenVK",
    "description": "Обзор основных возможностей движка",
    "duration": 120,
    "date": 1696680000,
    "views": 450,
    "image": "https://openvk.instance/videos/thumbs/84_130.jpg",
    "image_medium": "https://openvk.instance/videos/thumbs/84_320.jpg",
    "player": "https://openvk.instance/video_ext.php?oid=1&id=84&hash=abc123"
}
```
