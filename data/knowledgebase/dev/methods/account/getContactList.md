OpenVK-KB-Heading: account.getContactList

# account.getContactList

Returns the user's phone contact list.

> **Note (compatibility stub):** Phonebook contact sync is not implemented. The method returns an empty list (`{"count": 0, "items": []}`) to prevent crashes in official VK clients.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `offset` | integer | Result pagination offset. Default: `0`. |
| `count` | integer | Number of items to return (maximum `100`). Default: `100`. |
| `fields` | string | Comma-separated list of additional profile fields. |

### Result
Returns an object containing contact items:

| Field | Type | Description |
| --- | --- | --- |
| `count` | integer | Number of contacts (`0`). |
| `items` | array | Array of contact entries (`[]`). |

### Example Request
```http
POST /method/account.getContactList HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
