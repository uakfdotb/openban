CREATE TABLE openban_keys (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, name VARCHAR(32), ip VARCHAR(16), k VARCHAR(64), last_export INT);
CREATE TABLE openban_cache (banid INT NOT NULL PRIMARY KEY, time INT, status INT);
CREATE INDEX time ON openban_cache (time);

CREATE TABLE openban_banstemp (id INT NOT NULL PRIMARY KEY, name VARCHAR(15), server VARCHAR(100), ip VARCHAR(15), reason VARCHAR(255));
CREATE TABLE openban_targets (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, name VARCHAR(32), url VARCHAR(512), last_export INT);

ALTER TABLE bans ADD COLUMN openban_target VARCHAR(16) DEFAULT NULL;
ALTER TABLE bans ADD COLUMN openban_id INT DEFAULT NULL;
CREATE INDEX openban_target ON bans (openban_target);
CREATE INDEX openban_id ON bans (openban_id);
