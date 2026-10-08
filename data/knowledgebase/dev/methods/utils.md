OpenVK-KB-Heading: utils Methods

# utils Methods

The **utils** section provides utility API methods: getting server system time, resolving screen names into object IDs and types, parsing attachments, and calculating pagination offsets.

## Method List

* **[utils.getServerTime](/dev/methods/utils/getServerTime)** — returns current server time in unixtime format.
* **[utils.resolveScreenName](/dev/methods/utils/resolveScreenName)** — resolves object type (user or group) and identifier by screen name or short URL.
* **[utils.resolveGuid](/dev/methods/utils/resolveGuid)** — returns user information by Chandler user GUID.
* **[utils.resolveAttachments](/dev/methods/utils/resolveAttachments)** — parses attachment strings into API attachment structures.
* **[utils.resolveOffset](/dev/methods/utils/resolveOffset)** — calculates pagination offset required to jump directly to a target item (post, photo, or video).
