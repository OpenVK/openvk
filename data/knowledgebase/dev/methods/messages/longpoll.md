OpenVK-KB-Heading: User Long Poll API

# User Long Poll API

**Long Polling** is a technology that allows client applications to receive instant notifications about new messages and events in real time using persistent HTTP requests.

The server holds the incoming HTTP request open until at least one event occurs for the current user (such as a new message, read receipts, typing indicator, or presence change), or until the request timeout (`wait`) expires. As soon as an event occurs or the timeout expires, the server immediately returns a response. The client processes the updates and immediately issues the next request with the updated timestamp (`ts`).

---

## 1. Architecture & Workflow

Long Poll communication involves three steps:

```
[Client] --- 1. messages.getLongPollServer (lp_version=3) ---> [OpenVK API]
[Client] <-- 2. { server, key, ts, pts } ---------------------- [OpenVK API]

[Client] --- 3. GET https://{server}?act=a_check&key=...&ts=... -> [LongPoll Server]
              ... (held open for up to 25 seconds) ...
[Client] <-- 4. { ts: new_ts, updates: [ ... ] } --------------- [LongPoll Server]

[Client] --- 5. Next GET with new ts --------------------------> [LongPoll Server]
```

### Step 1: Acquiring Connection Credentials
Before polling, the client requests server credentials by calling **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)**:

```http
POST /method/messages.getLongPollServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

lp_version=3&need_pts=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

API Response:

```json
{
    "response": {
        "key": "4f9a0c7e2b1d",
        "server": "openvk.instance/nim",
        "ts": 1820350874,
        "pts": 450
    }
}
```

### Step 2: Sending a Long Poll Request
The client connects to the provided server URL:

```http
GET https://openvk.instance/nim?act=a_check&key=4f9a0c7e2b1d&ts=1820350874&wait=25&mode=2&version=3 HTTP/1.1
Host: openvk.instance
```

### Step 3: Processing Server Response
The server returns a new timestamp `ts` and a list of event arrays in `updates`:

```json
{
    "ts": 1820350910,
    "updates": [
        [
            4,
            1619489,
            561,
            2000000001,
            1710000000,
            "Hello world!",
            { "title": "Chat", "from": 123 },
            { "attach1_type": "photo", "attach1": "1_100" },
            18492048,
            42
        ]
    ]
}
```

---

## 2. Long Poll Request Parameters

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `act` | string | **Yes** | Always `a_check`. |
| `key` | string | **Yes** | Session key returned by `messages.getLongPollServer`. |
| `ts` | integer | **Yes** | Event timestamp/counter. Initially obtained from `messages.getLongPollServer`, then updated from each server response. |
| `wait` | integer | **Yes** | Timeout in seconds to wait for events (recommended: `25`, max: `90`). |
| `mode` | integer | No | Bitmask of additional returned data options (sum of flags). Default: `2`. |
| `version` | integer | No | Protocol version (`1`, `2`, or `3`). Default: `2`. |

### `mode` Flags

The `mode` parameter is formed as a bitwise sum of flags:
* `+2` — return `attachments` map in message events;
* `+8` — return extended events (in v0 returns event `101` with message object);
* `+32` — return `pts` in server responses (used for syncing with `messages.getLongPollHistory`);
* `+64` — return client platform in event `8` (user online);
* `+128` — return `random_id` field in message events;
* `+256` — return conversation message ID (`minor_id` / `conversation_message_id`).

---

## 3. Long Poll Error Codes (`failed`)

If the session key expires or event history is out of date, the server returns an object containing `failed`:

| Code | Server Response | Cause & Recommended Action |
| --- | --- | --- |
| `1` | `{"failed": 1, "ts": ...}` | Timestamp `ts` is outdated or events were lost. Update `ts` to the value from the response and retry. |
| `2` | `{"failed": 2}` | Session key has expired. Request new connection parameters via **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)**. |
| `3` | `{"failed": 3}` | User session data was lost on the server. Reconnect via **[messages.getLongPollServer](/dev/methods/messages/getLongPollServer)** with new `key` and `ts`. |
| `4` | `{"failed": 4, "min_version": 1, "max_version": 3}` | Unsupported protocol version in `version` parameter. Specify a supported version from `1` to `3`. |

> **Handling `failed: 1`:** When events are missed due to an outdated `ts`, it is recommended to call **[messages.getLongPollHistory](/dev/methods/messages/getLongPollHistory)** with the `pts` parameter to sync missing history.

---

## 4. The Three Long Poll Protocols (Versions 1, 2, and 3)

OpenVK supports three distinct User Long Poll protocols. The version is requested via the `version` parameter in the Long Poll HTTP request and `lp_version` in `messages.getLongPollServer`.

The primary difference lies in **Event `4` (New Message)** structure, peer addressing, and modern event capabilities.

---

### Version 1 (v1 — Legacy / VK API < 5.80)

Used by older clients designed for VK API 5.20 and below.

#### v1 Highlights:
* **Peer Addressing (`peer_id`):** For multi-user chats, the `2000000000` offset is subtracted. The returned ID is the plain chat identifier `1...N`. For direct messages, user ID is used.
* **Event 4 (New Message):**

```json
[
    4,
    "$message_id",
    "$flags",
    "$peer_id",
    "$timestamp",
    "$subject",
    "$text",
    "$attachments"
]
```

Where:

  1. `$message_id` *(integer)* — global message ID.
  2. `$flags` *(integer)* — message flag bitmask.
  3. `$peer_id` *(integer)* — destination (`chat_id` without 2000000000 for chats, `user_id` for DMs).
  4. `$timestamp` *(integer)* — Unix timestamp.
  5. **`$subject`** *(string)* — subject / chat title (usually empty string `""` or `" ... "`).
  6. **`$text`** *(string)* — message text (located at index **6**!).
  7. `$attachments` *(object, when mode=2)* — map of attachments and metadata (`attach1_type`, `attach1`, `from`, etc.).
* **Read Events 6 & 7:** `[6, $peer_id, $local_id]` and `[7, $peer_id, $local_id]` (without unread count).
* **Typing:** Legacy event codes `61` (DM) and `62` (chat).

---

### Version 2 (v2 — Intermediate / VK API 5.38 — 5.79)

Introduced unified peer identifiers and message editing support.

#### v2 Highlights:
* **Peer Addressing (`peer_id`):** Uses full unified `peer_id` without subtraction (`2000000000 + chat_id` for chats, `-group_id` for communities).
* **Event 4 (New Message):**

```json
[
    4,
    "$message_id",
    "$flags",
    "$peer_id",
    "$timestamp",
    "$subject",
    "$text",
    "$attachments",
    "$random_id"
]
```

  Field ordering retains `$subject` before `$text`, but adds `$random_id` when `mode=128`.
* **Event 5 (Edit Message):**
  `[5, id, mask, peer, ts, text, attach]` — sent when a message is edited.
* **Mass Delete / Restore:** Supported via events `13` (`[13, peer_id, local_id]`) and `14` (`[14, peer_id, local_id]`).

---

### Version 3 (v3 — Modern / VK API >= 5.80) — Recommended

The modern protocol standard required for all up-to-date applications and bots.

#### v3 Highlights:
* **Critical Difference in Event 4 (New Message):**
  The fields are reorganized — **message text is moved to position 5**, and the subject string is replaced by an `$extra_fields` metadata object:

```json
[
    4,
    "$message_id",
    "$flags",
    "$peer_id",
    "$timestamp",
    "$text",
    "$extra_fields",
    "$attachments",
    "$random_id",
    "$conversation_message_id"
]
```

Where:

  1. `$message_id` *(integer)* — global message ID.
  2. `$flags` *(integer)* — message flag bitmask.
  3. `$peer_id` *(integer)* — unified conversation peer ID (`user_id`, `2000000000 + chat_id`, `-group_id`).
  4. `$timestamp` *(integer)* — message Unix timestamp.
  5. **`$text`** *(string)* — **message text body** (at index 5!).
  6. **`$extra_fields`** *(object)* — metadata dictionary:
     * `title` *(string)* — conversation title;
     * `from` *(integer)* — real author ID when inside a multi-user conversation.
  7. **`$attachments`** *(object)* — attachments and metadata dictionary.
  8. **`$random_id`** *(integer)* — client-specified unique request ID.
  9. **`$conversation_message_id`** *(integer)* — conversation local message ID (`conversation_message_id`).
* **Read Events 6 & 7:** Includes the 4th parameter `$count` representing remaining unread dialog messages:
  * `[6, $peer_id, $local_id, $count]` — incoming messages read;
  * `[7, $peer_id, $local_id, $count]` — outgoing messages read.
* **Typing & Voice Recording (Multi-user):**
  Event codes `63` and `64` support arrays of multiple active users:
  * `[63, $user_ids, $peer_id, $total_count, $ts]` — typing indicator;
  * `[64, $user_ids, $peer_id, $total_count, $ts]` — voice message recording indicator.
* **State Sync:** Events `20` (`[20, $peer_id, $major_id]`) and `21` (`[21, $peer_id, $minor_id]`).

---

### Protocol Comparison Matrix

| Version | API Version | Message Text in Event 4 | Key Differences & Format |
| --- | --- | --- | --- |
| **v1** (Legacy) | `v < 5.80` | Index 6 (after subject) | Chats addressed by plain `chat_id` (1...N). Read events without count. Message format: `VKApiMessageLegacy`. |
| **v2** | `5.38 — 5.79` | Index 6 (after subject) | Full `peer_id` (2000000000 + chat_id). Supports editing (event 5), mass delete (13/14), and `random_id`. |
| **v3** (Recommended) | `v >= 5.80` | **Index 5** (before extra_fields) | Added `extra_fields` (index 6) and `conversation_message_id` (index 9). Events 6/7 include `count`. Multi-typing (63/64). Message format: `VKApiMessage`. |

---

## 5. Message Object Format (`ToVKApiStruct` vs `ToVKApiStructLegacy`)

When syncing messages via **[messages.getLongPollHistory](/dev/methods/messages/getLongPollHistory)** (or `messages.get`, `messages.getHistory`), the format returned in `messages.items` depends on the client API version:

### Modern Format (API v >= 5.80 — `VKApiMessage`)
Uses peer IDs and local conversation message IDs:

```json
{
    "id": 1619489,
    "conversation_message_id": 42,
    "date": 1710000000,
    "peer_id": 2000000001,
    "from_id": 123,
    "text": "Hello world!",
    "out": 0,
    "read_state": 1,
    "random_id": 18492048,
    "important": false,
    "attachments": [ ... ],
    "reply_message": { ... },
    "fwd_messages": [ ... ]
}
```

* Message text is in **`text`**;
* Author identifier is **`from_id`**;
* Target dialogue is **`peer_id`**;
* Quotes are structured objects in **`reply_message`**.

### Legacy Format (API v < 5.80 — `VKApiMessageLegacy`)
Uses classic user-centric and chat-centric fields:

```json
{
    "id": 1619489,
    "user_id": 123,
    "date": 1710000000,
    "read_state": 1,
    "out": 0,
    "title": "Chat",
    "body": "Hello world!",
    "chat_id": 1,
    "chat_active": [123, 456, 789],
    "users_count": 3,
    "admin_id": 123,
    "emoji": 0,
    "deleted": 0,
    "attachments": [ ... ],
    "fwd_messages": [ ... ]
}
```

* Message text is in **`body`** (instead of `text`);
* Destination / sender is in **`user_id`**;
* Multi-user chat ID is in **`chat_id`** (without the 2000000000 offset);
* Replies and quotes are represented in **`fwd_messages`**.

---

## 6. Message Flags

Message state is represented as a bitmask (sum of flags):

| Flag | Name | Description |
| --- | --- | --- |
| `+1` | `UNREAD` | Message is unread by the recipient. |
| `+2` | `OUTBOX` | Outgoing message (sent by the current user). |
| `+4` | `REPLIED` | Message has been replied to. |
| `+8` | `IMPORTANT` | Message is marked as important. |
| `+16` | `CHAT` | Message sent inside a multi-user chat. |
| `+32` | `FRIENDS` | Message sent by a friend. |
| `+64` | `SPAM` | Message marked as spam. |
| `+128` | `DELETED` | Message deleted for the current user. |
| `+256` | `FIXED` | Checked for spam. |
| `+512` | `MEDIA` | Message contains attachments. |
| `+65536` | `HIDDEN` | Hidden welcome or system message. |
| `+131072` | `DELETE_FOR_ALL` | Message deleted for everyone. |

#### Flag Checking Example:

```python
is_unread = bool(flags & 1)
is_outbox = bool(flags & 2)
is_important = bool(flags & 8)
```

---

## 7. Attachments & Metadata (`attachments`)

When passing `mode=2` in the Long Poll request, events 4 and 5 include an `attachments` object:

| Field | Example Value | Description |
| --- | --- | --- |
| `attach{$i}_type` | `"photo"`, `"video"`, `"audio"`, `"doc"`, `"sticker"`, `"wall"` | Type of `$i`-th attachment (`i >= 1`). |
| `attach{$i}` | `"1_456239017"` | Identifier of `$i`-th attachment (`{owner_id}_{id}`). |
| `attach{$i}_product_id` | `123` | Sticker product ID (`attach{$i}_type=sticker`). |
| `from` | `12345` | Real author ID when inside a conversation. |
| `reply` / `reply_to` | `4512` | ID of the replied-to message. |
| `fwd` | `"1_10,1_11"` | Comma-separated forwarded message IDs. |
| `emoji` | `1` | Flag indicating emoji presence. |
| `source_act` | `"chat_create"`,<br>`"chat_title_update"`,<br>`"chat_invite_user"`,<br>`"chat_kick_user"`,<br>`"chat_pin_message"` | Service action inside a chat. |
| `source_mid` | `12345` | Affected user ID for the service action. |

---

## 8. Event Code Reference

| Code | Event Signature | Description |
| --- | --- | --- |
| `0` | `[0, msg_id, 0]` | Message deleted. |
| `1` | `[1, msg_id, flags, peer_id]` | Replace all message flags with `flags`. |
| `2` | `[2, msg_id, mask, peer_id]` | Set message flags matching `mask` (`flags \|= mask`). |
| `3` | `[3, msg_id, mask, peer_id]` | Clear message flags matching `mask` (`flags &= ~mask`). |
| `4` | *Depends on protocol version (see Section 4)* | New message received or sent. |
| `5` | `[5, id, mask, peer, ts, text, attach]` | Message edited. |
| `6` | `[6, peer_id, local_id, count]` | Incoming messages read up to `local_id`. |
| `7` | `[7, peer_id, local_id, count]` | Outgoing messages read up to `local_id`. |
| `8` | `[8, -user_id, extra, ts]` | User `user_id` came online. Lowest byte `extra & 0xFF` contains platform ID. |
| `9` | `[9, -user_id, flags, ts]` | User `user_id` went offline (`flags`: 0 — logged out, 1 — timeout). |
| `10` | `[10, peer_id, mask]` | Clear community conversation flags. |
| `11` | `[11, peer_id, flags]` | Replace community conversation flags. |
| `12` | `[12, peer_id, mask]` | Set community conversation flags. |
| `13` | `[13, peer_id, local_id]` | Mass deletion of messages up to `local_id`. |
| `14` | `[14, peer_id, local_id]` | Mass restoration of messages up to `local_id`. |
| `20` | `[20, peer_id, major_id]` | Global history update in conversation (client cache invalidation). |
| `21` | `[21, peer_id, minor_id]` | Minor dialogue metadata change. |
| `51` | `[51, chat_id, self]` | Chat configuration changed (`self`: 1 — triggered by current user). |
| `52` | `[52, type_id, peer_id]` | Chat details changed (`1` — title, `2` — photo, `3` — admin, `5` — pin). |
| `61` | `[61, user_id, flags]` | User typing in private message (v1/v2). |
| `62` | `[62, user_id, chat_id]` | User typing in chat (v1/v2). |
| `63` | `[63, user_ids, peer, count, ts]` | Multi-user typing indicator (v3). |
| `64` | `[64, user_ids, peer, count, ts]` | Multi-user voice recording indicator (v3). |
| `70` | `[70, user_id, call_id]` | Incoming call started. |
| `80` | `[80, count]` | Unread conversation counter updated to `count`. |
| `114` | `[114, peer_id, sound, until]` | Notification settings changed for `peer_id`. |

---

## 9. Long Poll Client Example (Python)

Below is an example of a Long Poll polling loop handling errors and parsing incoming messages (**v3** protocol):

```python
import requests
import time

TOKEN = "YOUR_ACCESS_TOKEN"
API_URL = "https://openvk.instance/method/"

def get_lp_server():
    """Retrieve Long Poll server connection parameters"""
    resp = requests.post(API_URL + "messages.getLongPollServer", data={
        "lp_version": 3,
        "need_pts": 1,
        "access_token": TOKEN,
        "v": "5.138"
    }).json()
    
    if "error" in resp:
        raise Exception(f"API Error: {resp['error']}")
    return resp["response"]

def listen_longpoll():
    server_info = get_lp_server()
    server = server_info["server"]
    key = server_info["key"]
    ts = server_info["ts"]
    
    if not server.startswith("http"):
        server = f"https://{server}"

    print(f"Connected to LongPoll: {server}, ts={ts}")

    while True:
        try:
            url = f"{server}?act=a_check&key={key}&ts={ts}&wait=25&mode=2&version=3"
            response = requests.get(url, timeout=35).json()

            # Handle system errors
            if "failed" in response:
                code = response["failed"]
                if code == 1:
                    ts = response["ts"]
                    print(f"Failed 1: TS updated to {ts}")
                elif code in (2, 3):
                    print("Failed 2/3: Session expired, reconnecting...")
                    server_info = get_lp_server()
                    server = server_info["server"]
                    key = server_info["key"]
                    ts = server_info["ts"]
                    if not server.startswith("http"):
                        server = f"https://{server}"
                continue

            # Update timestamp for next request
            ts = response.get("ts", ts)

            # Process event stream
            for event in response.get("updates", []):
                code = event[0]

                # Event 4: New message (in protocol v3)
                if code == 4:
                    msg_id = event[1]
                    flags = event[2]
                    peer_id = event[3]
                    timestamp = event[4]
                    text = event[5]
                    extra_fields = event[6] if len(event) > 6 else {}
                    attachments = event[7] if len(event) > 7 else {}
                    cmid = event[9] if len(event) > 9 else None

                    is_outbox = bool(flags & 2)
                    sender_id = extra_fields.get("from", peer_id)

                    print(f"[{'Outbox' if is_outbox else 'Inbox'}] "
                          f"peer={peer_id} sender={sender_id} (msg_id={msg_id}, cmid={cmid}): {text}")

        except requests.RequestException as e:
            print(f"Network error: {e}. Retrying in 3 seconds...")
            time.sleep(3)

if __name__ == "__main__":
    listen_longpoll()
```
