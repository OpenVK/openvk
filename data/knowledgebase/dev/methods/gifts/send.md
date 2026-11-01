OpenVK-KB-Heading: gifts.send

# gifts.send

Sends a gift to the specified user for votes (coins) with an optional message and privacy setting.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | integer | **Required parameter**. Target recipient user ID. |
| `gift_id` | integer | **Required parameter**. ID of the gift from the catalog. |
| `message` | string | Attached greeting message text. Default: empty string. |
| `privacy` | integer | Privacy level: `0` — public sender name, `1` — anonymous sender. Default: `0`. |

### Result

Returns an object indicating operation status:
* On success:
  * `success` (integer) — `1`;
  * `user_ids` (integer) — Recipient user ID;
  * `withdraw_votes` (integer) — Number of votes deducted from user balance.
* If votes or gift quota are insufficient:
  * `success` (integer) — `0`;
  * `user_ids` (integer) — Recipient user ID;
  * `error` (string) — Error description (`"You don't have enough voices."` or `"You don't have any more of these gifts."`).

### Possible Errors

| Code | Description |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Commerce / votes system is disabled on this instance. |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |
| `9` | `Flood control: action too fast or too frequent.` — Action rate limit exceeded for sending gifts. |
| `15` | `Access denied` — Privacy settings prevent sending gifts to this user, or target user is banned. |
| `15` | `Invalid gift` — The specified `gift_id` does not exist in catalog. |

### Request Example
```http
POST /method/gifts.send HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_ids=2&gift_id=101&message=Happy+Holidays!&privacy=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": {
        "success": 1,
        "user_ids": 2,
        "withdraw_votes": 1
    }
}
```
