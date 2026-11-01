OpenVK-KB-Heading: gifts.delete

# gifts.delete

Deletes a previously sent gift.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `gift_id` | integer | **Required parameter**. ID of the sent gift record to delete. |

### Result

Returns `1` upon successful deletion.

### Possible Errors

| Code | Description |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Commerce / votes system is disabled on this instance. |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `15` | `Invalid gift` — Sent gift record with the specified ID was not found. |

### Request Example
```http
POST /method/gifts.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

gift_id=12&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": 1
}
```
