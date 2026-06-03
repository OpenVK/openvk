-- OpenVK Test Seed Data
-- Runs AFTER `openvkctl upgrade` has created all tables and applied migrations.
-- Uses fixed UUIDs and timestamps for deterministic screenshot tests.
-- Password for all users: "test123"
-- Hash: df6e26a476ff3425b1ed2728ade96730$174c5937a6c4f1294af213cb40caf00e

-- Reference timestamps (Unix): 2025-06-01 = 1748736000
SET @ts_base  = 1748736000;
SET @ts_day   = 86400;
SET @ts_hour  = 3600;

-- Drop auto-UUID triggers so we can insert with fixed IDs
DROP TRIGGER IF EXISTS bfiu_users;
DROP TRIGGER IF EXISTS bfiu_groups;
DROP TRIGGER IF EXISTS bfiu_tokens;

-- Keep only the two default groups from Chandler init
DELETE FROM ChandlerACLRelations;
DELETE FROM ChandlerACLGroupsPermissions;
DELETE FROM ChandlerACLUsersPermissions;
DELETE FROM ChandlerACLPermissionAliases;
DELETE FROM ChandlerLogs;
DELETE FROM ChandlerTokens;
DELETE FROM ChandlerUsers;
DELETE FROM ChandlerGroups WHERE id NOT IN ('c75fe4de-1e62-11ea-904d-42010aac0003', '594e6cb4-2a3a-11ea-9e1e-42010aac0003');

-- OpenVK groups (from init-static-db.sql)
INSERT INTO ChandlerGroups (id, name, color) VALUES
('e1000000-0000-0000-0000-000000000001', 'OVK\\Subteno', NULL),
('e1000000-0000-0000-0000-000000000002', 'OVK\\SupportAgents', NULL),
('e1000000-0000-0000-0000-000000000003', 'OVK\\Moderators', NULL),
('e1000000-0000-0000-0000-000000000004', 'OVK\\SpamAnalysts', NULL);

-- ChandlerUsers: fixed UUIDs for deterministic testing
INSERT INTO ChandlerUsers (id, login, passwordHash, deleted) VALUES
('00000000-0000-0000-0000-000000000001', 'admin@test.local',    'df6e26a476ff3425b1ed2728ade96730$174c5937a6c4f1294af213cb40caf00e', 0),
('00000000-0000-0000-0000-000000000002', 'alice@test.local',    'df6e26a476ff3425b1ed2728ade96730$174c5937a6c4f1294af213cb40caf00e', 0),
('00000000-0000-0000-0000-000000000003', 'bob@test.local',      'df6e26a476ff3425b1ed2728ade96730$174c5937a6c4f1294af213cb40caf00e', 0),
('00000000-0000-0000-0000-000000000004', 'charlie@test.local',  'df6e26a476ff3425b1ed2728ade96730$174c5937a6c4f1294af213cb40caf00e', 0);

-- ACL: assign users to groups (admin -> Administrators + Users, others -> Users)
INSERT INTO ChandlerACLRelations (user, `group`, priority) VALUES
('00000000-0000-0000-0000-000000000001', '594e6cb4-2a3a-11ea-9e1e-42010aac0003', 64),
('00000000-0000-0000-0000-000000000001', 'c75fe4de-1e62-11ea-904d-42010aac0003', 32),
('00000000-0000-0000-0000-000000000002', 'c75fe4de-1e62-11ea-904d-42010aac0003', 32),
('00000000-0000-0000-0000-000000000003', 'c75fe4de-1e62-11ea-904d-42010aac0003', 32),
('00000000-0000-0000-0000-000000000004', 'c75fe4de-1e62-11ea-904d-42010aac0003', 32);

-- Admin -> all admin groups
INSERT INTO ChandlerACLRelations (user, `group`, priority) VALUES
('00000000-0000-0000-0000-000000000001', 'e1000000-0000-0000-0000-000000000001', 64),
('00000000-0000-0000-0000-000000000001', 'e1000000-0000-0000-0000-000000000002', 64),
('00000000-0000-0000-0000-000000000001', 'e1000000-0000-0000-0000-000000000003', 64),
('00000000-0000-0000-0000-000000000001', 'e1000000-0000-0000-0000-000000000004', 64);

-- ChandlerACLGroupsPermissions (support agents, moderators, spam analysts)
INSERT INTO ChandlerACLGroupsPermissions (`group`, model, context, permission, status) VALUES
('e1000000-0000-0000-0000-000000000002', 'openvk\\Web\\Models\\Entities\\TicketReply', 0, 'write', 1),
('e1000000-0000-0000-0000-000000000003', 'openvk\\Web\\Models\\Entities\\Report', 0, 'admin', 1),
('e1000000-0000-0000-0000-000000000004', 'openvk\\Web\\Models\\Entities\\Ban', 0, 'write', 1);

-- Profiles
-- Alice: female, verified
INSERT INTO profiles (id, user, first_name, last_name, pseudo, sex, email, coins, rating, since, verified, reputation, shortcode, online, birthday, privacy, left_menu, city, status, about, interests, fav_music, fav_films, fav_books, style, show_rating, activated, microblog, main_page, deleted) VALUES
(2, '00000000-0000-0000-0000-000000000002', 'Alice', 'Testova', NULL, 1, 'alice@test.local', 500, 250, '2024-06-01 10:00:00', 1, 500, 'alice', @ts_base, 0, 1099511627775, 1099511627775, 'Arcadia Bay', 'Hello, I am Alice ✨', 'Just a test user.', 'music, coding', 'Rock, Jazz', 'Inception, Matrix', '1984', 'ovk', 1, 1, 1, 1, 0);

-- Bob: male, not verified
INSERT INTO profiles (id, user, first_name, last_name, pseudo, sex, email, coins, rating, since, verified, reputation, shortcode, online, birthday, privacy, left_menu, city, status, about, style, show_rating, activated, microblog, main_page, deleted) VALUES
(3, '00000000-0000-0000-0000-000000000003', 'Bob', 'Testov', NULL, 2, 'bob@test.local', 200, 100, '2024-07-15 14:30:00', 0, 300, 'bob', @ts_base, 0, 1099511627775, 1099511627775, 'Springfield', 'Bob here.', 'Just a chill guy.', 'ovk', 1, 1, 0, 0, 0);

-- Charlie: male, verified
INSERT INTO profiles (id, user, first_name, last_name, pseudo, sex, email, coins, rating, since, verified, reputation, shortcode, online, birthday, privacy, left_menu, city, status, about, interests, style, show_rating, activated, microblog, main_page, deleted) VALUES
(4, '00000000-0000-0000-0000-000000000004', 'Charlie', 'Testovich', NULL, 2, 'charlie@test.local', 100, 50, '2024-08-20 08:00:00', 1, 100, 'charlie', @ts_base, 0, 1099511627775, 1099511627775, 'Gotham', 'Charlie reporting!', 'Photographer and traveler.', 'photography, travel', 'ovk', 1, 1, 0, 0, 0);

-- Admin (profile 1) - update existing from init-static-db
-- Must also update `user` UUID to match our new ChandlerUsers record
UPDATE profiles SET
  user       = '00000000-0000-0000-0000-000000000001',
  first_name = 'System',
  last_name  = 'Administrator',
  email      = 'admin@test.local',
  shortcode  = 'sysop',
  online     = @ts_base,
  since      = '2023-01-01 00:00:00',
  verified   = 1,
  reputation = 1000,
  coins      = 9999,
  rating     = 9999,
  privacy    = 1099511627775,
  left_menu  = 1099511627775,
  city       = 'Arcadia Bay',
  status     = 'Default System Administrator account',
  style      = 'ovk'
WHERE id = 1;

-- Posts on Alice's wall (wall = profile id)
INSERT INTO posts (id, owner, wall, virtual_id, created, edited, content, flags, nsfw, ad, pinned, anonymous, deleted, suggested) VALUES
(1, 2, 2, 1, @ts_base - 7 * @ts_day,        NULL, 'Hello world! This is my very first post on OpenVK. Nice to meet everyone! 🎉', NULL, 0, 0, FALSE, FALSE, 0, 0),
(2, 2, 2, 2, @ts_base - 3 * @ts_day,        NULL, 'Just had a great cup of coffee and watched the sunrise. Life is good.', NULL, 0, 0, FALSE, FALSE, 0, 0),
(3, 2, 2, 3, @ts_base - 1 * @ts_day,        NULL, 'Check out this cool photo I took yesterday!', NULL, 0, 0, FALSE, FALSE, 0, 0),
(4, 2, 2, 4, @ts_base - 12 * @ts_hour,      NULL, 'Does anyone know a good book about machine learning? 📚', NULL, 0, 0, FALSE, FALSE, 0, 0),
(5, 2, 2, 5, @ts_base - 2 * @ts_hour,       NULL, 'Pinned announcement: I am now accepting friend requests!', NULL, 0, 0, TRUE, FALSE, 0, 0);

-- Bob posts on his wall
INSERT INTO posts (id, owner, wall, virtual_id, created, edited, content, flags, nsfw, ad, pinned, anonymous, deleted, suggested) VALUES
(6, 3, 3, 1, @ts_base - 5 * @ts_day,        NULL, 'Hey everyone! Bob here. Just joined this cool network.', NULL, 0, 0, FALSE, FALSE, 0, 0),
(7, 3, 3, 2, @ts_base - 2 * @ts_day,        NULL, 'Anyone up for a game of chess? ♟️', NULL, 0, 0, FALSE, FALSE, 0, 0),
(8, 3, 3, 3, @ts_base - 6 * @ts_hour,       NULL, 'Sharing my new track - hope you like it!', NULL, 0, 0, FALSE, FALSE, 0, 0);

-- Charlie posts
INSERT INTO posts (id, owner, wall, virtual_id, created, edited, content, flags, nsfw, ad, pinned, anonymous, deleted, suggested) VALUES
(9, 4, 4, 1, @ts_base - 4 * @ts_day,        NULL, 'Hello from Charlie! Here is a photo from my latest trip.', NULL, 0, 0, FALSE, FALSE, 0, 0),
(10, 4, 4, 2, @ts_base - 1 * @ts_day,       NULL, 'Great article about photography techniques: [link]', NULL, 0, 0, FALSE, FALSE, 0, 0);

-- Bob posts on Alice's wall
INSERT INTO posts (id, owner, wall, virtual_id, created, edited, content, flags, nsfw, ad, pinned, anonymous, deleted, suggested) VALUES
(11, 3, 2, 6, @ts_base - 4 * @ts_hour,      NULL, 'Alice, great photos! Keep it up! 👍', NULL, 0, 0, FALSE, FALSE, 0, 0);

-- Comments on Alice's post #3
INSERT INTO comments (id, owner, model, target, created, edited, content, flags, ad, anonymous, deleted, virtual_id) VALUES
(1, 3, 'openvk\\Web\\Models\\Entities\\Post', 3, @ts_base - 20 * @ts_hour, NULL, 'Wow, this is beautiful! Where was this taken?', NULL, 0, FALSE, 0, 1),
(2, 4, 'openvk\\Web\\Models\\Entities\\Post', 3, @ts_base - 19 * @ts_hour, NULL, 'Amazing shot! 📸', NULL, 0, FALSE, 0, 2),
(3, 2, 'openvk\\Web\\Models\\Entities\\Post', 3, @ts_base - 18 * @ts_hour, NULL, 'Thanks guys! It was taken at the park downtown.', NULL, 0, FALSE, 0, 3);

-- Comment on Bob's post #7
INSERT INTO comments (id, owner, model, target, created, edited, content, flags, ad, anonymous, deleted, virtual_id) VALUES
(4, 2, 'openvk\\Web\\Models\\Entities\\Post', 7, @ts_base - 1 * @ts_day, NULL, 'I would love to play! Count me in.', NULL, 0, FALSE, 0, 1);

-- Subscriptions (friendships)
-- Alice <-> Bob (mutual)
INSERT INTO subscriptions_new (handle, initiator, targetModel, targetId, targetWallHandle, shortStatus, detailedStatus, created, updated) VALUES
(1, 2, 'user', 3, 3, 1, 1, @ts_base - 30 * @ts_day, @ts_base - 30 * @ts_day);
INSERT INTO subscriptions_new (handle, initiator, targetModel, targetId, targetWallHandle, shortStatus, detailedStatus, created, updated) VALUES
(2, 3, 'user', 2, 2, 1, 1, @ts_base - 30 * @ts_day, @ts_base - 30 * @ts_day);

-- Alice -> Charlie (Alice follows Charlie)
INSERT INTO subscriptions_new (handle, initiator, targetModel, targetId, targetWallHandle, shortStatus, detailedStatus, created, updated) VALUES
(3, 2, 'user', 4, 4, 1, 1, @ts_base - 14 * @ts_day, @ts_base - 14 * @ts_day);

-- Bob -> Alice (already from mutual)
-- Charlie -> Alice
INSERT INTO subscriptions_new (handle, initiator, targetModel, targetId, targetWallHandle, shortStatus, detailedStatus, created, updated) VALUES
(4, 4, 'user', 2, 2, 1, 1, @ts_base - 7 * @ts_day, @ts_base - 7 * @ts_day);

-- Old subscriptions table (for backward compat)
INSERT INTO subscriptions (follower, model, target) VALUES
(2, 'user', 3),
(3, 'user', 2),
(2, 'user', 4),
(4, 'user', 2);

-- Likes
-- Alice's post #1: liked by Bob, Charlie
INSERT INTO likes (origin, model, target, `index`) VALUES
(3, 'openvk\\Web\\Models\\Entities\\Post', 1, 1),
(4, 'openvk\\Web\\Models\\Entities\\Post', 1, 2);

-- Alice's post #3: liked by Bob, Charlie, Alice herself
INSERT INTO likes (origin, model, target, `index`) VALUES
(3, 'openvk\\Web\\Models\\Entities\\Post', 3, 3),
(4, 'openvk\\Web\\Models\\Entities\\Post', 3, 4),
(2, 'openvk\\Web\\Models\\Entities\\Post', 3, 5);

-- Bob's post #6: liked by Alice
INSERT INTO likes (origin, model, target, `index`) VALUES
(2, 'openvk\\Web\\Models\\Entities\\Post', 6, 6);

-- Comment #1 on post #3: liked by Alice
INSERT INTO likes (origin, model, target, `index`) VALUES
(2, 'openvk\\Web\\Models\\Entities\\Comment', 1, 7);

-- Charlie's post #9: liked by Alice
INSERT INTO likes (origin, model, target, `index`) VALUES
(2, 'openvk\\Web\\Models\\Entities\\Post', 9, 8);

-- Group (club) owned by Alice
INSERT INTO `groups` (id, name, about, owner, shortcode, verified, type, closed, wall) VALUES
(1, 'Test Group', 'A test group for screenshot testing. Welcome!', 2, 'testgroup', 1, 1, 0, 1);

INSERT INTO group_coadmins (user, club, comment, id) VALUES
(2, 1, 'Owner', 1),
(3, 1, 'Moderator', 2);

-- Group wall posts (Bob posts to group, Alice posts as owner)
INSERT INTO posts (id, owner, wall, virtual_id, created, edited, content, flags, nsfw, ad, deleted, suggested) VALUES
(12, 3, 1, 1, @ts_base - 10 * @ts_hour, NULL, 'Hello group! Bob here, just joined.', NULL, 0, 0, 0, 0),
(13, 2, 1, 2, @ts_base - 5 * @ts_hour,  NULL, 'Welcome to the group everyone! Feel free to post.', NULL, 0, 0, 0, 0);

-- Album for Alice
INSERT INTO albums (id, owner, name, description, access_pragma, special_type, created, deleted) VALUES
(1, 2, 'Vacation Photos', 'Photos from my summer vacation', 255, 0, @ts_base - 60 * @ts_day, 0);

-- Notification offset for Alice (so notifications page loads)
UPDATE profiles SET notification_offset = @ts_base - @ts_hour WHERE id = 2;

-- Migration history (mark all migrations as applied so upgrade command is no-op)
INSERT INTO ovk_upgrade_history (`level`, `timestamp`, `operator`) VALUES
(0,  @ts_base - 365 * @ts_day, 'test-seed'),
(1,  @ts_base - 365 * @ts_day, 'test-seed'),
(2,  @ts_base - 365 * @ts_day, 'test-seed'),
(3,  @ts_base - 365 * @ts_day, 'test-seed'),
(4,  @ts_base - 365 * @ts_day, 'test-seed'),
(5,  @ts_base - 365 * @ts_day, 'test-seed'),
(6,  @ts_base - 365 * @ts_day, 'test-seed'),
(7,  @ts_base - 365 * @ts_day, 'test-seed'),
(8,  @ts_base - 365 * @ts_day, 'test-seed'),
(9,  @ts_base - 365 * @ts_day, 'test-seed'),
(10, @ts_base - 365 * @ts_day, 'test-seed'),
(11, @ts_base - 365 * @ts_day, 'test-seed'),
(12, @ts_base - 365 * @ts_day, 'test-seed'),
(13, @ts_base - 365 * @ts_day, 'test-seed'),
(14, @ts_base - 365 * @ts_day, 'test-seed'),
(15, @ts_base - 365 * @ts_day, 'test-seed'),
(16, @ts_base - 365 * @ts_day, 'test-seed'),
(17, @ts_base - 365 * @ts_day, 'test-seed'),
(18, @ts_base - 365 * @ts_day, 'test-seed'),
(19, @ts_base - 365 * @ts_day, 'test-seed'),
(20, @ts_base - 365 * @ts_day, 'test-seed'),
(21, @ts_base - 365 * @ts_day, 'test-seed'),
(22, @ts_base - 365 * @ts_day, 'test-seed'),
(23, @ts_base - 365 * @ts_day, 'test-seed'),
(24, @ts_base - 365 * @ts_day, 'test-seed'),
(25, @ts_base - 365 * @ts_day, 'test-seed'),
(26, @ts_base - 365 * @ts_day, 'test-seed'),
(27, @ts_base - 365 * @ts_day, 'test-seed'),
(28, @ts_base - 365 * @ts_day, 'test-seed'),
(29, @ts_base - 365 * @ts_day, 'test-seed'),
(30, @ts_base - 365 * @ts_day, 'test-seed'),
(31, @ts_base - 365 * @ts_day, 'test-seed'),
(32, @ts_base - 365 * @ts_day, 'test-seed'),
(33, @ts_base - 365 * @ts_day, 'test-seed'),
(34, @ts_base - 365 * @ts_day, 'test-seed'),
(35, @ts_base - 365 * @ts_day, 'test-seed'),
(36, @ts_base - 365 * @ts_day, 'test-seed'),
(37, @ts_base - 365 * @ts_day, 'test-seed'),
(38, @ts_base - 365 * @ts_day, 'test-seed'),
(39, @ts_base - 365 * @ts_day, 'test-seed'),
(40, @ts_base - 365 * @ts_day, 'test-seed'),
(41, @ts_base - 365 * @ts_day, 'test-seed'),
(42, @ts_base - 365 * @ts_day, 'test-seed'),
(43, @ts_base - 365 * @ts_day, 'test-seed'),
(44, @ts_base - 365 * @ts_day, 'test-seed'),
(45, @ts_base - 365 * @ts_day, 'test-seed'),
(46, @ts_base - 365 * @ts_day, 'test-seed'),
(47, @ts_base - 365 * @ts_day, 'test-seed'),
(48, @ts_base - 365 * @ts_day, 'test-seed'),
(49, @ts_base - 365 * @ts_day, 'test-seed'),
(50, @ts_base - 365 * @ts_day, 'test-seed'),
(51, @ts_base - 365 * @ts_day, 'test-seed'),
(52, @ts_base - 365 * @ts_day, 'test-seed'),
(53, @ts_base - 365 * @ts_day, 'test-seed'),
(54, @ts_base - 365 * @ts_day, 'test-seed'),
(55, @ts_base - 365 * @ts_day, 'test-seed'),
(56, @ts_base - 365 * @ts_day, 'test-seed'),
(57, @ts_base - 365 * @ts_day, 'test-seed'),
(58, @ts_base - 365 * @ts_day, 'test-seed'),
(59, @ts_base - 365 * @ts_day, 'test-seed'),
(60, @ts_base - 365 * @ts_day, 'test-seed');

-- Recreate UUID triggers for normal operation
CREATE TRIGGER bfiu_users  BEFORE INSERT ON ChandlerUsers  FOR EACH ROW SET new.id = uuid();
CREATE TRIGGER bfiu_groups BEFORE INSERT ON ChandlerGroups FOR EACH ROW SET new.id = uuid();
CREATE TRIGGER bfiu_tokens BEFORE INSERT ON ChandlerTokens FOR EACH ROW SET new.token = uuid();

-- Reset auto_increment to avoid conflicts
ALTER TABLE profiles    AUTO_INCREMENT = 100;
ALTER TABLE posts       AUTO_INCREMENT = 100;
ALTER TABLE comments   AUTO_INCREMENT = 100;
ALTER TABLE albums     AUTO_INCREMENT = 100;
ALTER TABLE `groups`   AUTO_INCREMENT = 100;
ALTER TABLE likes      AUTO_INCREMENT = 100;
