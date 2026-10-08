OpenVK-KB-Heading: Список методов API

# Список методов API

Для вызова любого метода необходимо отправить GET или POST запрос по адресу:
`https://{domain}/method/{method_name}`

---

## [execute](/dev/methods/execute)

* **[execute](/dev/methods/execute)** — универсальный метод для выполнения пакета API-вызовов и алгоритмов на языке VKScript за один HTTP-запрос.

---

## [account](/dev/methods/account)

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

## [activity](/dev/methods/activity)

* **[activity.online](/dev/methods/activity/online)** — помечает текущего пользователя как online с указанием платформы.

## [apps](/dev/methods/apps)

* **[apps.getMiniAppsCatalog](/dev/methods/apps/getMiniAppsCatalog)** — возвращает каталог мини-приложений.
* **[apps.getMiniAppsCatalogSearch](/dev/methods/apps/getMiniAppsCatalogSearch)** — поиск по каталогу мини-приложений.

## [audio](/dev/methods/audio)

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

## [board](/dev/methods/board)

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

## [docs](/dev/methods/docs)

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

## [friends](/dev/methods/friends)

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

## [gifts](/dev/methods/gifts)

* **[gifts.get](/dev/methods/gifts/get)** — возвращает список полученных пользователем подарков.
* **[gifts.send](/dev/methods/gifts/send)** — отправляет подарок пользователю за голоса (монеты).
* **[gifts.delete](/dev/methods/gifts/delete)** — удаляет отправленный подарок.
* **[gifts.getCategories](/dev/methods/gifts/getCategories)** — возвращает список категорий каталога подарков.
* **[gifts.getGiftsInCategory](/dev/methods/gifts/getGiftsInCategory)** — возвращает список подарков в указанной категории каталога.

## [groups](/dev/methods/groups)

* **[groups.get](/dev/methods/groups/get)** — возвращает список сообществ пользователя.
* **[groups.getById](/dev/methods/groups/getById)** — возвращает подробную информацию о сообществах по их идентификаторам или коротким именам.
* **[groups.search](/dev/methods/groups/search)** — осуществляет поиск по сообществам платформы.
* **[groups.join](/dev/methods/groups/join)** — вступает в сообщество или подписывается на публичную страницу.
* **[groups.leave](/dev/methods/groups/leave)** — покидает сообщество или отписывается от публичной страницы.
* **[groups.edit](/dev/methods/groups/edit)** — редактирует основные настройки и параметры сообщества.
* **[groups.getMembers](/dev/methods/groups/getMembers)** — возвращает список участников (подписчиков) сообщества.
* **[groups.getSettings](/dev/methods/groups/getSettings)** — возвращает текущие настройки и уровни доступа разделов сообщества.
* **[groups.isMember](/dev/methods/groups/isMember)** — проверяет, является ли пользователь участником сообщества.
* **[groups.ban](/dev/methods/groups/ban)** — добавляет пользователя в черный список сообщества.
* **[groups.unban](/dev/methods/groups/unban)** — удаляет пользователя из черного списка сообщества.
* **[groups.getBanned](/dev/methods/groups/getBanned)** — возвращает список пользователей, находящихся в черном списке сообщества.

## [internal](/dev/methods/internal)

* **[internal.getNotifications](/dev/methods/internal/getNotifications)** — возвращает системные уведомления для старых мобильных клиентов (заглушка).
* **[internal.giveMeException](/dev/methods/internal/giveMeException)** — вызывает тестовое исключение для проверки обработки ошибок.

## [likes](/dev/methods/likes)

* **[likes.add](/dev/methods/likes/add)** — добавляет отметку «Мне нравится» к указанному объекту.
* **[likes.delete](/dev/methods/likes/delete)** — удаляет отметку «Мне нравится» с указанного объекта.
* **[likes.isLiked](/dev/methods/likes/isLiked)** — проверяет, находится ли объект в списке «Мне нравится» указанного пользователя.
* **[likes.getList](/dev/methods/likes/getList)** — возвращает список идентификаторов или профилей пользователей, поставивших отметку «Мне нравится».

## [messages](/dev/methods/messages)

* **[messages.send](/dev/methods/messages/send)** — отправляет сообщение, вложение или стикер.
* **[messages.edit](/dev/methods/messages/edit)** — редактирует отправленное сообщение.
* **[messages.delete](/dev/methods/messages/delete)** — удаляет сообщения.
* **[messages.restore](/dev/methods/messages/restore)** — восстанавливает удаленное сообщение.
* **[messages.get](/dev/methods/messages/get)** — возвращает список входящих/исходящих сообщений.
* **[messages.getById](/dev/methods/messages/getById)** — возвращает сообщения по идентификаторам.
* **[messages.getByConversationMessageId](/dev/methods/messages/getByConversationMessageId)** — возвращает сообщения по локальным ID беседы.
* **[messages.getHistory](/dev/methods/messages/getHistory)** — возвращает историю сообщений диалога или беседы.
* **[messages.getHistoryAttachments](/dev/methods/messages/getHistoryAttachments)** — возвращает медиавложения из истории переписки.
* **[messages.getImportantMessages](/dev/methods/messages/getImportantMessages)** — возвращает список важных сообщений.
* **[messages.markAsRead](/dev/methods/messages/markAsRead)** — помечает сообщения как прочитанные.
* **[messages.markAsImportant](/dev/methods/messages/markAsImportant)** — помечает сообщения как важные.
* **[messages.pin](/dev/methods/messages/pin)** — закрепляет сообщение в беседе.
* **[messages.unpin](/dev/methods/messages/unpin)** — открепляет сообщение.
* **[messages.search](/dev/methods/messages/search)** — осуществляет поиск по тексту сообщений.
* **[messages.getConversations](/dev/methods/messages/getConversations)** — возвращает список бесед в современном формате (v >= 5.80).
* **[messages.getConversationsById](/dev/methods/messages/getConversationsById)** — возвращает беседы по их peer_id.
* **[messages.searchConversations](/dev/methods/messages/searchConversations)** — осуществляет поиск по беседам.
* **[messages.getConversationMembers](/dev/methods/messages/getConversationMembers)** — возвращает участников беседы.
* **[messages.deleteConversation](/dev/methods/messages/deleteConversation)** — удаляет всю переписку в беседе.
* **[messages.markAsImportantConversation](/dev/methods/messages/markAsImportantConversation)** — помечает беседу как важную.
* **[messages.markAsAnsweredConversation](/dev/methods/messages/markAsAnsweredConversation)** — помечает беседу как отвеченную.
* **[messages.getDialogs](/dev/methods/messages/getDialogs)** — возвращает список диалогов (устаревший метод).
* **[messages.searchDialogs](/dev/methods/messages/searchDialogs)** — осуществляет поиск по диалогам (устаревший метод).
* **[messages.deleteDialog](/dev/methods/messages/deleteDialog)** — удаляет диалог (устаревший метод).
* **[messages.createChat](/dev/methods/messages/createChat)** — создает новую групповую беседу (чат).
* **[messages.getChat](/dev/methods/messages/getChat)** — возвращает информацию о групповой беседе.
* **[messages.getChatUsers](/dev/methods/messages/getChatUsers)** — возвращает список участников беседы.
* **[messages.addChatUser](/dev/methods/messages/addChatUser)** — добавляет пользователя в беседу.
* **[messages.removeChatUser](/dev/methods/messages/removeChatUser)** — исключает пользователя из беседы.
* **[messages.editChat](/dev/methods/messages/editChat)** — изменяет название беседы.
* **[messages.setChatPhoto](/dev/methods/messages/setChatPhoto)** — устанавливает обложку беседы.
* **[messages.deleteChatPhoto](/dev/methods/messages/deleteChatPhoto)** — удаляет обложку беседы.
* **[messages.getInviteLink](/dev/methods/messages/getInviteLink)** — возвращает ссылку-приглашение в беседу.
* **[messages.getChatPreview](/dev/methods/messages/getChatPreview)** — возвращает информацию о беседе по ссылке-приглашению.
* **[messages.joinChatByInviteLink](/dev/methods/messages/joinChatByInviteLink)** — вступает в беседу по ссылке-приглашению.
* **[messages.joinChatByTopic](/dev/methods/messages/joinChatByTopic)** — вступает в беседу, привязанную к теме обсуждений.
* **[messages.setMemberRole](/dev/methods/messages/setMemberRole)** — назначает роль участнику беседы.
* **[messages.setChatPermissions](/dev/methods/messages/setChatPermissions)** — настраивает права действий в беседе.
* **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)** — возвращает параметры подключения к LongPoll.
* **[messages.getLongPollHistory](/dev/methods/messages/getLongPollHistory)** — возвращает историю событий LongPoll.
* **[messages.getDiff](/dev/methods/messages/getDiff)** — возвращает дифференциальную синхронизацию для клиентов.
* **[messages.setActivity](/dev/methods/messages/setActivity)** — передает статус набора текста или записи голоса.
* **[messages.getLastActivity](/dev/methods/messages/getLastActivity)** — возвращает время последней активности пользователя.
* **[messages.allowMessagesFromGroup](/dev/methods/messages/allowMessagesFromGroup)** — разрешает отправку сообщений от сообщества.
* **[messages.denyMessagesFromGroup](/dev/methods/messages/denyMessagesFromGroup)** — запрещает отправку сообщений от сообщества.
* **[messages.isMessagesFromGroupAllowed](/dev/methods/messages/isMessagesFromGroupAllowed)** — проверяет разрешение на сообщения от сообщества.
* **[messages.createFolder](/dev/methods/messages/createFolder)** — создает папку диалогов.
* **[messages.getFolders](/dev/methods/messages/getFolders)** — возвращает список папок диалогов.
* **[messages.updateFolder](/dev/methods/messages/updateFolder)** — обновляет параметры папки диалогов.
* **[messages.deleteFolder](/dev/methods/messages/deleteFolder)** — удаляет папку диалогов.
* **[messages.reorderFolders](/dev/methods/messages/reorderFolders)** — изменяет порядок папок.
* **[messages.getCounters](/dev/methods/messages/getCounters)** — возвращает счетчики сообщений.
* **[messages.getMessageViewers](/dev/methods/messages/getMessageViewers)** — возвращает список прочитавших сообщение.
* **[messages.report](/dev/methods/messages/report)** — отправляет жалобу на спам/нарушение в сообщении.

## [users](/dev/methods/users)

* **[users.get](/dev/methods/users/get)** — возвращает расширенную информацию о пользователях.
* **[users.search](/dev/methods/users/search)** — возвращает список пользователей в соответствии с заданным критерием поиска.
