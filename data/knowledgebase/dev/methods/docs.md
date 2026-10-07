OpenVK-KB-Heading: docs methods

# docs methods

The **docs** section provides methods for working with user and community documents: retrieving files, searching, editing metadata, deleting, copying, and grouping by types and tags.

### Document Management
* **[docs.get](/dev/methods/docs/get)** — returns documents of the current user or community with type and tag filtering.
* **[docs.getById](/dev/methods/docs/getById)** — returns information about documents by their identifiers and access keys.
* **[docs.getTypes](/dev/methods/docs/getTypes)** — returns document type categories and file counts per category.
* **[docs.getTags](/dev/methods/docs/getTags)** — returns a list of tags used in user documents.
* **[docs.search](/dev/methods/docs/search)** — searches public documents across the platform by title or tags.
* **[docs.add](/dev/methods/docs/add)** — copies a document to the authorized user's documents.
* **[docs.edit](/dev/methods/docs/edit)** — edits document title, tags, folder, and visibility.
* **[docs.delete](/dev/methods/docs/delete)** — deletes a document from the collection.
* **[docs.restore](/dev/methods/docs/restore)** — restores a deleted document.

### Uploading Documents
* **[docs.getUploadServer](/dev/methods/docs/getUploadServer)** — returns upload server address for documents (stub).
* **[docs.getWallUploadServer](/dev/methods/docs/getWallUploadServer)** — returns wall document upload server address (stub).
* **[docs.save](/dev/methods/docs/save)** — saves an uploaded document file (stub).
