OpenVK-KB-Heading: docs.edit

# docs.edit

Edits the title, tags, folder, and owner visibility of a document.

### Authorization
Requires user authorization (`access_token`) with the `docs` permission.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the document owner. **Required.** |
| `doc_id` | integer | Document identifier. **Required.** |
| `title` | string | New document title (up to 128 characters). |
| `tags` | string | Comma-separated list of tags (up to 256 characters). |
| `folder_id` | integer | Folder identifier (`0` — private, `3` — public). |
| `owner_hidden` | integer | `1` to hide the author, `0` to show author, `-1` to leave unchanged. Default: `-1`. |

### Result

Returns `1` on success, or `0` on error.

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `1150` | `Invalid document id` |
| `1152` | `Invalid document title` |
| `1153` | `Access to document is denied` |
| `1154` | `Invalid tags` |

### Request Example
```http
POST /method/docs.edit HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&doc_id=45&title=New_Title.pdf&tags=docs,work&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
