OpenVK-KB-Heading: audio.getRecommendations

# audio.getRecommendations

Returns a list of recommended audio tracks for the current user.

> **Note:** In the current version of OpenVK, this method is a compatibility stub returning an empty list.

### Authorization
No authorization is strictly required.

### Parameters
None.

### Result

For API versions 5.0 and higher, returns an object with an empty list:
```json
{
    "count": 0,
    "items": []
}
```

For API versions prior to 5.0, returns `[0]`.

### Request Example
```http
POST /method/audio.getRecommendations HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
