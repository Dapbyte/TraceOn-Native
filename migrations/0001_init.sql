-- TraceOn — Initial Schema Migration
-- Run: mysql -u root -p traceon < migrations/0001_init.sql
-- Requires: database `traceon` already created

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── USERS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  avatar_path VARCHAR(255) NULL DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── WORKSPACES ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS workspaces (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  deadline    DATE NULL DEFAULT NULL,
  invite_code VARCHAR(10) NOT NULL,
  owner_id    INT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invite_code (invite_code),
  CONSTRAINT fk_workspace_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── WORKSPACE MEMBERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS workspace_members (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id  INT NOT NULL,
  user_id       INT NOT NULL,
  role          ENUM('Owner','Admin','Member') NOT NULL,
  status        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at   TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_workspace_user (workspace_id, user_id),
  CONSTRAINT fk_wm_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_wm_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
  INDEX idx_wm_workspace_id (workspace_id),
  INDEX idx_wm_user_id      (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CARDS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cards (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id  INT NOT NULL,
  title         VARCHAR(100) NOT NULL,
  deadline      DATE NULL DEFAULT NULL,
  created_by    INT NULL DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_card_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_card_creator   FOREIGN KEY (created_by)   REFERENCES users(id)      ON DELETE SET NULL,
  INDEX idx_card_workspace_id (workspace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CARD ACCESS ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS card_access (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  card_id    INT NOT NULL,
  user_id    INT NOT NULL,
  granted_by INT NULL DEFAULT NULL,
  granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_card_user (card_id, user_id),
  CONSTRAINT fk_ca_card       FOREIGN KEY (card_id)    REFERENCES cards(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_user       FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ca_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ca_card_id (card_id),
  INDEX idx_ca_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TODOS ────────────────────────────────────────────────────────────────
-- No soft delete. Deletion is permanent. No deleted_at column.
CREATE TABLE IF NOT EXISTS todos (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  card_id    INT NOT NULL,
  title      VARCHAR(255) NOT NULL,
  status     ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  created_by INT NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_todo_card    FOREIGN KEY (card_id)    REFERENCES cards(id) ON DELETE CASCADE,
  CONSTRAINT fk_todo_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_todo_card_id     (card_id),
  INDEX idx_todo_card_status (card_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ACTIVITIES ───────────────────────────────────────────────────────────
-- card_id has NO FK intentionally: historical logs must survive card deletion
CREATE TABLE IF NOT EXISTS activities (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  workspace_id  INT NOT NULL,
  user_id       INT NULL DEFAULT NULL,
  card_id       INT NULL DEFAULT NULL,
  activity_type VARCHAR(50) NOT NULL,
  old_value     TEXT NULL DEFAULT NULL,
  new_value     TEXT NULL DEFAULT NULL,
  action        TEXT NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_act_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
  CONSTRAINT fk_act_user      FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE SET NULL,
  INDEX idx_act_workspace_created (workspace_id, created_at),
  INDEX idx_act_user_id           (user_id),
  INDEX idx_act_created_at        (created_at),
  FULLTEXT INDEX ft_act_action    (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── LOGIN ATTEMPTS ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS login_attempts (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  ip_address      VARCHAR(45) NOT NULL,
  attempt_count   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  last_attempt_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  blocked_until   TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_la_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
