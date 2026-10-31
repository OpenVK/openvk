OpenVK-KB-Heading: Объект Audio

# Объект Audio

Объект **Audio** описывает музыкальный аудиотрек ВКонтакте / OpenVK. Формат полей зависит от версии API, переданной в параметре `v`.

---

## Версия API 5.0 и выше (v >= 5.0)

В версиях API 5.0+ используются современные имена полей:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор аудиозаписи. |
| `owner_id` | integer | Идентификатор владельца аудиозаписи (пользователя или сообщества). |
| `artist` | string | Имя исполнителя / автора трека. |
| `title` | string | Название аудиозаписи. |
| `duration` | integer | Длительность аудиозаписи в секундах. |
| `url` | string | Прямая ссылка на воспроизведение аудиофайла (MP3). |
| `lyrics_id` | integer | *(Опционально)* Идентификатор текста песни (если текст добавлен). |
| `album_id` | integer | *(Опционально)* Идентификатор альбома аудиозаписи. |
| `genre_id` | integer | Числовой идентификатор жанра (`1` — Rock, `2` — Pop, `3` — Rap & Hip-Hop, `4` — Easy Listening, `5` — Dance & House, `6` — Instrumental, `7` — Metal, `8` — Dubstep, `9` — Drum & Bass, `10` — Trance, `11` — Chanson, `12` — Ethnic, `13` — Acoustic & Vocal, `14` — Reggae, `15` — Classical, `16` — Indie Pop, `17` — Speech, `18` — Other). |
| `genre_str` | string | Строковое название жанра. |
| `added` | boolean | Добавлена ли композиция в библиотеку текущего пользователя. |
| `editable` | boolean | Может ли текущий пользователь редактировать данную аудиозапись. |
| `searchable` | boolean | Доступна ли аудиозапись для глобального поиска. |
| `explicit` | boolean | Содержит ли трек ненормативную лексику / Explicit контент. |
| `listens` | integer | *(Опционально)* Количество прослушиваний (возвращается, если `editable = true`). |

### Пример объекта (v >= 5.0)
```json
{
    "id": 1,
    "owner_id": 1,
    "artist": "Neverlove",
    "title": "Инструктор по вождению",
    "duration": 184,
    "url": "https://openvk.instance/audio/get/1.mp3",
    "genre_id": 1,
    "genre_str": "Rock",
    "added": true,
    "editable": false,
    "searchable": true,
    "explicit": true
}
```

---

## До версии API 5.0 (v < 5.0)

В версиях API 3.x и 4.x используются устаревшие имена полей:

| Поле | Тип | Описание |
| --- | --- | --- |
| `aid` / `id` | integer | Идентификатор аудиозаписи. |
| `oid` / `owner_id` | integer | Идентификатор владельца аудиозаписи. |
| `performer` / `artist` | string | Имя исполнителя. |
| `title` | string | Название аудиозаписи. |
| `duration` | integer | Длительность в секундах. |
| `url` | string | Ссылка на MP3-файл. |
| `lyricsID` / `lyrics_id` | integer | *(Опционально)* Идентификатор текста песни. |
| `genre` / `genre_id` | integer | Числовой идентификатор жанра. |

### Пример объекта (v < 5.0)
```json
{
    "aid": 1,
    "oid": 1,
    "performer": "Neverlove",
    "title": "Инструктор по вождению",
    "duration": 184,
    "url": "https://openvk.instance/audio/get/1.mp3",
    "genre": 1
}
```
