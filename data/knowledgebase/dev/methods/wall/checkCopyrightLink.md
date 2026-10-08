OpenVK-KB-Heading: wall.checkCopyrightLink

# wall.checkCopyrightLink

Validates the correctness and safety of an external copyright source URL for a wall post.

### Authorization
Requires user authorization token.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `link` | string | **Required.** External source URL to validate. |

### Result

Returns `1` if the link passes validation and is accepted as a copyright source.

### Possible Errors

| Code | Description |
| --- | --- |
| `3102` | `Specified link is incorrect` — link is malformed or inaccessible. |
| `3103` | `Specified link is incorrect (too long)` — link length exceeds allowed limits. |
| `3104` | `Link is suspicious` — link recognized as suspicious or malicious. |

### Example Request
```http
POST /method/wall.checkCopyrightLink HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

link=https://example.com/article/123&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": 1
}
```
