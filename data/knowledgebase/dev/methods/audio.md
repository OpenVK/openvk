OpenVK-KB-Heading: audio methods

# audio methods

The **audio** section provides methods for managing audio tracks, playlists (albums), searching music, fetching lyrics, broadcasting playback status, and retrieving popular tracks or new feeds.

### Audios
* **[audio.get](/dev/methods/audio/get)** — returns a list of audio files of a user or community with support for filtering, shuffling, and ID lookup.
* **[audio.getById](/dev/methods/audio/getById)** — returns information about audio files by their IDs (up to 6,000 at once).
* **[audio.search](/dev/methods/audio/search)** — searches audio files with artist and lyrics filters and sorting options.
* **[audio.getCount](/dev/methods/audio/getCount)** — returns the total count of audio files for a user or community.
* **[audio.getPopular](/dev/methods/audio/getPopular)** — returns popular audio files with optional genre filter.
* **[audio.getFeed](/dev/methods/audio/getFeed)** — returns the feed of newly uploaded audio files.
* **[audio.getLyrics](/dev/methods/audio/getLyrics)** — returns track lyrics by track ID.
* **[audio.add](/dev/methods/audio/add)** — copies an audio file to user or community collection.
* **[audio.delete](/dev/methods/audio/delete)** — removes an audio file from user or community collection.
* **[audio.restore](/dev/methods/audio/restore)** — restores a deleted audio file.
* **[audio.edit](/dev/methods/audio/edit)** — edits audio metadata (artist, title, lyrics, genre, searchability).

### Broadcasting & Activity
* **[audio.setBroadcast](/dev/methods/audio/setBroadcast)** — sets an audio track as the playback status for a user or community.
* **[audio.getBroadcastList](/dev/methods/audio/getBroadcastList)** — returns friends or communities currently broadcasting music in their status.
* **[audio.beacon](/dev/methods/audio/beacon)** — reports playback ping to register a listen and update stats.

### Playlists & Albums
* **[audio.getAlbums](/dev/methods/audio/getAlbums)** — returns playlists (albums) of a user or community.
* **[audio.getPlaylistById](/dev/methods/audio/getPlaylistById)** — returns playlist information by its ID and owner ID.
* **[audio.searchAlbums](/dev/methods/audio/searchAlbums)** — searches playlists with ordering and ownership filtering.
* **[audio.addAlbum](/dev/methods/audio/addAlbum)** — creates a new playlist.
* **[audio.editAlbum](/dev/methods/audio/editAlbum)** — edits playlist title and description.
* **[audio.deleteAlbum](/dev/methods/audio/deleteAlbum)** — deletes a playlist.
* **[audio.moveToAlbum](/dev/methods/audio/moveToAlbum)** — adds audio files to a playlist or binds them to an album.
* **[audio.removeFromAlbum](/dev/methods/audio/removeFromAlbum)** — removes audio files from a playlist.
* **[audio.bookmarkAlbum](/dev/methods/audio/bookmarkAlbum)** — bookmarks a playlist for the current user.
* **[audio.unBookmarkAlbum](/dev/methods/audio/unBookmarkAlbum)** — removes a playlist from user bookmarks.

### Miscellaneous
* **[audio.getRecommendations](/dev/methods/audio/getRecommendations)** — returns recommended audio files (compatibility stub).
* **[audio.isLagtrain](/dev/methods/audio/isLagtrain)** — OpenVK easter egg method checking if track is Lagtrain.
* **[audio.subscribeToQueue](/dev/methods/audio/subscribeToQueue)** — playback queue subscription stub.