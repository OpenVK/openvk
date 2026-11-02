-- Grant "view hidden pages" to every group that currently has instance admin access.
-- The permission is context-bound to 0 because the admin group UI always writes context 0.
INSERT INTO `ChandlerACLGroupsPermissions` (`group`, `model`, `context`, `permission`, `status`)
SELECT src.`group`, 'admin', 0, 'viewHidden', 1
FROM (
    SELECT DISTINCT `group`
    FROM `ChandlerACLGroupsPermissions`
    WHERE `model` = 'admin' AND `permission` = 'access' AND `status` = 1
) AS src
WHERE NOT EXISTS (
    SELECT 1
    FROM `ChandlerACLGroupsPermissions` existing
    WHERE existing.`group` = src.`group`
      AND existing.`model` = 'admin'
      AND existing.`context` = 0
      AND existing.`permission` = 'viewHidden'
);
