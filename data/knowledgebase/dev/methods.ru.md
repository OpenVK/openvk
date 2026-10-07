OpenVK-KB-Heading: Список методов API

# Список методов API

Для вызова любого метода необходимо отправить GET или POST запрос по адресу:
`https://{domain}/method/{method_name}`

---

## execute

* **[execute](/dev/methods/execute)** — универсальный метод для выполнения пакета API-вызовов и алгоритмов на языке VKScript за один HTTP-запрос.

---

## account

* **[account.ban](/dev/methods/account/ban)** — добавляет пользователя в черный список.
* **[account.unban](/dev/methods/account/unban)** — удаляет пользователя из черного списка.
* **[account.getBanned](/dev/methods/account/getBanned)** — возвращает список заблокированных пользователей.
* **[account.getBalance](/dev/methods/account/getBalance)** — возвращает баланс голосов (монет) текущего пользователя.
* **[account.getCounters](/dev/methods/account/getCounters)** — возвращает счетчики непрочитанных сообщений, уведомлений и заявок.
* **[account.getInfo](/dev/methods/account/getInfo)** — возвращает общую информацию о текущем аккаунте и настройках.
* **[account.getProfileInfo](/dev/methods/account/getProfileInfo)** — возвращает расширенную информацию о профиле пользователя.
* **[account.getOvkSettings](/dev/methods/account/getOvkSettings)** — возвращает специфичные настройки интерфейса OpenVK.
* **[account.getViewerId](/dev/methods/account/getViewerId)** — возвращает идентификатор текущего пользователя.
* **[account.getAppPermissions](/dev/methods/account/getAppPermissions)** — возвращает битовую маску настроек и прав приложения.
* **[account.saveProfileInfo](/dev/methods/account/saveProfileInfo)** — сохраняет основную информацию профиля.
* **[account.saveInterestsInfo](/dev/methods/account/saveInterestsInfo)** — сохраняет информацию об интересах и увлечениях.
* **[account.sendVotes](/dev/methods/account/sendVotes)** — передает голоса другому пользователю.
* **[account.setOnline](/dev/methods/account/setOnline)** — помечает текущего пользователя как online на 5 минут.
* **[account.setOffline](/dev/methods/account/setOffline)** — помечает текущего пользователя как offline.
* **[account.registerDevice](/dev/methods/account/registerDevice)** — регистрирует устройство для получения Push-уведомлений.
* **[account.unregisterDevice](/dev/methods/account/unregisterDevice)** — отменяет регистрацию устройства для Push-уведомлений.
* **[account.setSilenceMode](/dev/methods/account/setSilenceMode)** — настраивает беззвучный режим уведомлений.
* **[account.getPushSettings](/dev/methods/account/getPushSettings)** — возвращает настройки Push-уведомлений.
* **[account.get](/dev/methods/account/get)** — возвращает базовую структуру данных аккаунта.
* **[account.getMulti](/dev/methods/account/getMulti)** — возвращает информацию о сессии мультиаккаунта.
* **[account.getPrivacySettings](/dev/methods/account/getPrivacySettings)** — возвращает структуру настроек приватности.
* **[account.getContactList](/dev/methods/account/getContactList)** — возвращает телефонную книгу контактов.
* **[account.getHelpHints](/dev/methods/account/getHelpHints)** — возвращает подсказки справки.
* **[account.getBadgesSettings](/dev/methods/account/getBadgesSettings)** — возвращает настройки значков приложения.
* **[account.getToggles](/dev/methods/account/getToggles)** — возвращает состояние переключателей функций (feature toggles).

## activity

* **[activity.online](/dev/methods/activity/online)** — помечает текущего пользователя как online с указанием платформы.

## apps

* **[apps.getMiniAppsCatalog](/dev/methods/apps/getMiniAppsCatalog)** — возвращает каталог мини-приложений.
* **[apps.getMiniAppsCatalogSearch](/dev/methods/apps/getMiniAppsCatalogSearch)** — поиск по каталогу мини-приложений.

## audio

* **[audio.get](/dev/methods/audio/get)** — возвращает список аудиозаписей пользователя или сообщества.
* **[audio.getById](/dev/methods/audio/getById)** — возвращает информацию об аудиозаписях по их идентификаторам.
* **[audio.search](/dev/methods/audio/search)** — осуществляет поиск по аудиозаписям.
* **[audio.getCount](/dev/methods/audio/getCount)** — возвращает количество аудиозаписей пользователя или сообщества.
* **[audio.getPopular](/dev/methods/audio/getPopular)** — возвращает список популярных аудиозаписей.
* **[audio.getFeed](/dev/methods/audio/getFeed)** — возвращает ленту недавно загруженных аудиозаписей.
* **[audio.getLyrics](/dev/methods/audio/getLyrics)** — возвращает текст песни по ее идентификатору.
* **[audio.add](/dev/methods/audio/add)** — копирует аудиозапись в коллекцию текущего пользователя или сообщества.
* **[audio.delete](/dev/methods/audio/delete)** — удаляет аудиозапись из коллекции пользователя или сообщества.
* **[audio.restore](/dev/methods/audio/restore)** — восстанавливает удаленную аудиозапись.
* **[audio.edit](/dev/methods/audio/edit)** — редактирует метаданные аудиозаписи.
* **[audio.setBroadcast](/dev/methods/audio/setBroadcast)** — транслирует текущую аудиозапись в статус.
* **[audio.getBroadcastList](/dev/methods/audio/getBroadcastList)** — возвращает список друзей или сообществ, транслирующих музыку в статус.
* **[audio.beacon](/dev/methods/audio/beacon)** — фиксирует факт прослушивания трека.
* **[audio.getAlbums](/dev/methods/audio/getAlbums)** — возвращает список плейлистов пользователя или сообщества.
* **[audio.getPlaylistById](/dev/methods/audio/getPlaylistById)** — возвращает информацию о плейлисте по ID.
* **[audio.searchAlbums](/dev/methods/audio/searchAlbums)** — выполняет поиск по плейлистам.
* **[audio.addAlbum](/dev/methods/audio/addAlbum)** — создает новый плейлист.
* **[audio.editAlbum](/dev/methods/audio/editAlbum)** — редактирует название и описание плейлиста.
* **[audio.deleteAlbum](/dev/methods/audio/deleteAlbum)** — удаляет плейлист.
* **[audio.moveToAlbum](/dev/methods/audio/moveToAlbum)** — добавляет аудиозаписи в плейлист или привязывает к альбому.
* **[audio.removeFromAlbum](/dev/methods/audio/removeFromAlbum)** — удаляет аудиозаписи из плейлиста.
* **[audio.bookmarkAlbum](/dev/methods/audio/bookmarkAlbum)** — сохраняет плейлист в закладки.
* **[audio.unBookmarkAlbum](/dev/methods/audio/unBookmarkAlbum)** — удаляет плейлист из закладок.
* **[audio.getRecommendations](/dev/methods/audio/getRecommendations)** — возвращает рекомендации аудиозаписей.
* **[audio.isLagtrain](/dev/methods/audio/isLagtrain)** — проверяет, является ли аудиозапись треком Lagtrain.
* **[audio.subscribeToQueue](/dev/methods/audio/subscribeToQueue)** — заглушка подписки на очередь воспроизведения.

## board

* **[board.getTopics](/dev/methods/board/getTopics)** — возвращает список обсуждений сообщества.
* **[board.getComments](/dev/methods/board/getComments)** — возвращает список комментариев в теме обсуждения.
* **[board.addTopic](/dev/methods/board/addTopic)** — создает новую тему в обсуждениях сообщества.
* **[board.addChatTopic](/dev/methods/board/addChatTopic)** — создает тему в обсуждениях, привязанную к групповому чату.
* **[board.createComment](/dev/methods/board/createComment)** — добавляет новый комментарий в обсуждение.
* **[board.editTopic](/dev/methods/board/editTopic)** — редактирует заголовок обсуждения.
* **[board.closeTopic](/dev/methods/board/closeTopic)** — закрывает тему обсуждения.
* **[board.openTopic](/dev/methods/board/openTopic)** — открывает тему обсуждения.
* **[board.fixTopic](/dev/methods/board/fixTopic)** — закрепляет тему в обсуждениях.
* **[board.unfixTopic](/dev/methods/board/unfixTopic)** — открепляет тему в обсуждениях.
* **[board.deleteTopic](/dev/methods/board/deleteTopic)** — удаляет тему обсуждения.

## docs

* **[docs.get](/dev/methods/docs/get)** — возвращает список документов пользователя или сообщества.
* **[docs.getById](/dev/methods/docs/getById)** — возвращает информацию о документах по их идентификаторам.
* **[docs.getTypes](/dev/methods/docs/getTypes)** — возвращает категории типов документов и счетчики файлов.
* **[docs.getTags](/dev/methods/docs/getTags)** — возвращает список тегов документов.
* **[docs.search](/dev/methods/docs/search)** — осуществляет поиск по документам.
* **[docs.add](/dev/methods/docs/add)** — копирует документ в коллекцию пользователя.
* **[docs.edit](/dev/methods/docs/edit)** — редактирует метаданные документа.
* **[docs.delete](/dev/methods/docs/delete)** — удаляет документ.
* **[docs.restore](/dev/methods/docs/restore)** — восстанавливает удаленный документ.
* **[docs.getUploadServer](/dev/methods/docs/getUploadServer)** — возвращает адрес сервера для загрузки документов.
* **[docs.getWallUploadServer](/dev/methods/docs/getWallUploadServer)** — возвращает адрес сервера для загрузки документов на стену.
* **[docs.save](/dev/methods/docs/save)** — сохраняет документ после загрузки.

## friends

* **[friends.get](/dev/methods/friends/get)** — возвращает список идентификаторов друзей пользователя или подробную информацию о них.
* **[friends.getOnline](/dev/methods/friends/getOnline)** — возвращает список идентификаторов друзей пользователя, которые сейчас находятся на сайте (онлайн).
* **[friends.getMutual](/dev/methods/friends/getMutual)** — возвращает список общих друзей между пользователями.
* **[friends.getRequests](/dev/methods/friends/getRequests)** — возвращает список заявок на добавление в друзья (входящих или исходящих).
* **[friends.getSuggestions](/dev/methods/friends/getSuggestions)** — возвращает список рекомендуемых друзей (заглушка).
* **[friends.search](/dev/methods/friends/search)** — осуществляет поиск по списку друзей пользователя.
* **[friends.areFriends](/dev/methods/friends/areFriends)** — возвращает статус дружбы с указанными пользователями.
* **[friends.add](/dev/methods/friends/add)** — отправляет заявку на добавление в друзья или одобряет входящую заявку.
* **[friends.delete](/dev/methods/friends/delete)** — удаляет пользователя из списка друзей или отклоняет заявку.
* **[friends.edit](/dev/methods/friends/edit)** — редактирует списки друга (заглушка).
* **[friends.getLists](/dev/methods/friends/getLists)** — возвращает список списков друзей (заглушка).
* **[friends.editList](/dev/methods/friends/editList)** — редактирует список друзей (заглушка).
* **[friends.deleteList](/dev/methods/friends/deleteList)** — удаляет список друзей (заглушка).

## users

* **[users.get](/dev/methods/users/get)** — возвращает расширенную информацию о пользователях.
* **[users.search](/dev/methods/users/search)** — возвращает список пользователей в соответствии с заданным критерием поиска.
