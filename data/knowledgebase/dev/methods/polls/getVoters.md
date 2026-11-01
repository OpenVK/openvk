OpenVK-KB-Heading: polls.getVoters

# polls.getVoters

Returns a list of users who voted for the specified answer option in a public (non-anonymous) poll.

### Authorization
Requires user authorization (`access_token`) with the `wall` scope.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `poll_id` | integer | **Required.** Poll ID. |
| `answer_ids` | integer | **Required.** Answer option ID to retrieve voters for. |
| `offset` | integer | Offset needed to return a specific subset of voters. Default: `0`. |
| `count` | integer | Number of voters to return. Default: `6`. |

### Result

Returns an array containing voting results objects:

```json
{
    "response": [
        {
            "answer_id": 1,
            "users": {
                "items": [
                    {
                        "id": 1,
                        "first_name": "Pavel",
                        "last_name": "Durov",
                        "photo_50": "https://openvk.instance/avatars/1_50.jpeg",
                        "photo_100": "https://openvk.instance/avatars/1_100.jpeg",
                        "photo_200": "https://openvk.instance/avatars/1_200.jpeg"
                    }
                ]
            }
        }
    ]
}
```

### Possible Errors

| Code | Description |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — User is not authorized. |
| `15` | `Access denied` — Poll not found or is anonymous (voter list hidden). |

### Example Request
```http
POST /method/polls.getVoters HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&answer_ids=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": [
        {
            "answer_id": 1,
            "users": {
                "items": [
                    {
                        "id": 1,
                        "first_name": "Pavel",
                        "last_name": "Durov",
                        "photo_50": "https://openvk.instance/avatars/1_50.jpeg",
                        "photo_100": "https://openvk.instance/avatars/1_100.jpeg",
                        "photo_200": "https://openvk.instance/avatars/1_200.jpeg"
                    }
                ]
            }
        }
    ]
}
```
