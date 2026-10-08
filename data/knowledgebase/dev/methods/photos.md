OpenVK-KB-Heading: Photos methods

# Photos methods

The **photos** section contains methods for managing photos, albums, and comments, as well as obtaining upload server URLs for uploading images to various parts of the platform (profiles, walls, messages, albums).

## Method List

### Albums
* **[photos.createAlbum](/dev/methods/photos/createAlbum)** — creates an empty photo album.
* **[photos.editAlbum](/dev/methods/photos/editAlbum)** — edits the title and description of a photo album.
* **[photos.getAlbums](/dev/methods/photos/getAlbums)** — returns a list of photo albums of a user or community.
* **[photos.getAlbumsCount](/dev/methods/photos/getAlbumsCount)** — returns the number of photo albums of a user or community.
* **[photos.deleteAlbum](/dev/methods/photos/deleteAlbum)** — deletes a photo album.

### Photos
* **[photos.get](/dev/methods/photos/get)** — returns photos from an album or by IDs.
* **[photos.getById](/dev/methods/photos/getById)** — returns information about photos by their IDs.
* **[photos.getAll](/dev/methods/photos/getAll)** — returns all photos of a user or community in reverse chronological order.
* **[photos.getUserPhotos](/dev/methods/photos/getUserPhotos)** — returns all photos of a user (alias for `photos.getAll`).
* **[photos.edit](/dev/methods/photos/edit)** — edits a photo's caption.
* **[photos.delete](/dev/methods/photos/delete)** — deletes one or more photos.

### Photo Uploads
* **[photos.getUploadServer](/dev/methods/photos/getUploadServer)** — returns the upload URL for uploading photos to an album.
* **[photos.save](/dev/methods/photos/save)** — saves photos after successful upload to an album.
* **[photos.getOwnerPhotoUploadServer](/dev/methods/photos/getOwnerPhotoUploadServer)** — returns the upload URL for uploading a profile or community main photo.
* **[photos.saveOwnerPhoto](/dev/methods/photos/saveOwnerPhoto)** — saves a profile or community main photo after uploading.
* **[photos.getWallUploadServer](/dev/methods/photos/getWallUploadServer)** — returns the upload URL for uploading photos to a wall.
* **[photos.saveWallPhoto](/dev/methods/photos/saveWallPhoto)** — saves a photo for publishing on a wall.
* **[photos.getMessagesUploadServer](/dev/methods/photos/getMessagesUploadServer)** — returns the upload URL for uploading photos to a private message.
* **[photos.saveMessagesPhoto](/dev/methods/photos/saveMessagesPhoto)** — saves a photo for sending in a private message.
* **[photos.getChatUploadServer](/dev/methods/photos/getChatUploadServer)** — returns the upload URL for uploading a group chat photo.

### Comments
* **[photos.getComments](/dev/methods/photos/getComments)** — returns a list of comments on a photo.
* **[photos.createComment](/dev/methods/photos/createComment)** — adds a new comment to a photo.
* **[photos.addComment](/dev/methods/photos/addComment)** — adds a comment to a photo (alias for `photos.createComment`).

### Tags
* **[photos.getTags](/dev/methods/photos/getTags)** — returns a list of tags on a photo.
* **[photos.putTag](/dev/methods/photos/putTag)** — adds a user tag on a photo.
* **[photos.deleteTag](/dev/methods/photos/deleteTag)** — deletes a tag from a photo.
* **[photos.confirmTag](/dev/methods/photos/confirmTag)** — confirms a tag on a photo.
