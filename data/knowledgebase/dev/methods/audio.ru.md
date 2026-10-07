OpenVK-KB-Heading: Методы секции audio

# Методы секции audio

Секция **audio** содержит методы для работы с аудиозаписями, плейлистами (альбомами), поиском треков, текстами песен, трансляцией статуса прослушивания и получением списков рекомендаций и новинок.

### Аудиозаписи
* **[audio.get](/dev/methods/audio/get)** — возвращает список аудиозаписей пользователя или сообщества с возможностью фильтрации, перемешивания и выборки по ID.
* **[audio.getById](/dev/methods/audio/getById)** — возвращает информацию об аудиозаписях по их идентификаторам (до 6000 за один запрос).
* **[audio.search](/dev/methods/audio/search)** — осуществляет поиск по аудиозаписям с поддержкой фильтрации по исполнителю, тексту песни и сортировки.
* **[audio.getCount](/dev/methods/audio/getCount)** — возвращает количество аудиозаписей пользователя или сообщества.
* **[audio.getPopular](/dev/methods/audio/getPopular)** — возвращает список популярных аудиозаписей с фильтрацией по жанрам.
* **[audio.getFeed](/dev/methods/audio/getFeed)** — возвращает список недавно загруженных аудиозаписей (лента новинок).
* **[audio.getLyrics](/dev/methods/audio/getLyrics)** — возвращает текст песни по ее идентификатору.
* **[audio.add](/dev/methods/audio/add)** — копирует аудиозапись в коллекцию текущего пользователя или сообщества.
* **[audio.delete](/dev/methods/audio/delete)** — удаляет аудиозапись из коллекции пользователя или сообщества.
* **[audio.restore](/dev/methods/audio/restore)** — восстанавливает удаленную аудиозапись в коллекцию.
* **[audio.edit](/dev/methods/audio/edit)** — редактирует метаданные аудиозаписи (исполнитель, название, текст, жанр, видимость в поиске).

### Трансляция и активность
* **[audio.setBroadcast](/dev/methods/audio/setBroadcast)** — устанавливает текущую аудиозапись в статус (трансляция прослушивания) пользователя или сообщества.
* **[audio.getBroadcastList](/dev/methods/audio/getBroadcastList)** — возвращает список друзей или сообществ, транслирующих музыку в статус.
* **[audio.beacon](/dev/methods/audio/beacon)** — фиксирует факт прослушивания трека для обновления счетчика прослушиваний и истории.

### Плейлисты и альбомы
* **[audio.getAlbums](/dev/methods/audio/getAlbums)** — возвращает список плейлистов (альбомов) пользователя или сообщества.
* **[audio.getPlaylistById](/dev/methods/audio/getPlaylistById)** — возвращает базовую информацию о плейлисте по его ID и ID владельца.
* **[audio.searchAlbums](/dev/methods/audio/searchAlbums)** — выполняет поиск по плейлистам с сортировкой и фильтрацией.
* **[audio.addAlbum](/dev/methods/audio/addAlbum)** — создает новый плейлист.
* **[audio.editAlbum](/dev/methods/audio/editAlbum)** — редактирует название и описание плейлиста.
* **[audio.deleteAlbum](/dev/methods/audio/deleteAlbum)** — удаляет плейлист.
* **[audio.moveToAlbum](/dev/methods/audio/moveToAlbum)** — добавляет аудиозаписи в плейлист или привязывает их к альбому.
* **[audio.removeFromAlbum](/dev/methods/audio/removeFromAlbum)** — удаляет аудиозаписи из плейлиста.
* **[audio.bookmarkAlbum](/dev/methods/audio/bookmarkAlbum)** — сохраняет плейлист в закладки пользователя.
* **[audio.unBookmarkAlbum](/dev/methods/audio/unBookmarkAlbum)** — удаляет плейлист из закладок пользователя.

### Дополнительно
* **[audio.getRecommendations](/dev/methods/audio/getRecommendations)** — возвращает рекомендации аудиозаписей (заглушка совместимости).
* **[audio.isLagtrain](/dev/methods/audio/isLagtrain)** — специальный метод OpenVK, проверяющий наличие песни Lagtrain.
* **[audio.subscribeToQueue](/dev/methods/audio/subscribeToQueue)** — заглушка подписки на очередь воспроизведения.
