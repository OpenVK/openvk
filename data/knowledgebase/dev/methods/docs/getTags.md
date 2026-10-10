OpenVK-KB-Heading: docs.getTags

# docs.getTags

Returns a list of unique tags used in documents of the specified owner (up to 50 tags).

### Authorization
Requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `owner_id` | integer | Identifier of the documents owner (positive for current user, negative for community). Default: current user ID. |
| `type` | integer | Filter tags by document type (`1..8`, `0` for all types). Default: `0`. |

### Result

Returns an array of tag strings (`["tag1", "tag2", ...]`).

### Error Codes

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` |
| `15` | `Access denied` |

### Request Example
```http
POST /method/docs.getTags HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

type=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        "work",
        "report",
        "contract"
    ]
}
```
