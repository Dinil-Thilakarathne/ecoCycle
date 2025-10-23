-- 001_core_users_full.sql
-- Full user & auth related schema for EcoCycle
-- Run in MySQL / MariaDB after creating the database (USE ecocycle;)
-- Charset & collation assumed utf8mb4 / utf8mb4_unicode_ci

SET NAMES utf8mb4;
SET time_zone = '+00:00';

/* =============================================
   ROLES
   ============================================= */
CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  label VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO roles (name,label) VALUES
  ('admin','Administrator'),
  ('company','Company'),
  ('collector','Collector'),
  ('customer','Customer')
ON DUPLICATE KEY UPDATE label = VALUES(label);

/* =============================================
   USERS
   ============================================= */
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  username VARCHAR(100) NULL UNIQUE,
  nic VARCHAR(30) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  profile_image_path VARCHAR(255) NULL,
  status ENUM('active','pending','suspended') DEFAULT 'active',
  email_verified_at TIMESTAMP NULL,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_nic ON users(nic);

/* =============================================
   USER PROFILES (OPTIONAL)
   ============================================= */
CREATE TABLE IF NOT EXISTS user_profiles (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  full_name VARCHAR(150) NULL,
  phone VARCHAR(40) NULL,
  address_line1 VARCHAR(190) NULL,
  address_line2 VARCHAR(190) NULL,
  city VARCHAR(120) NULL,
  region VARCHAR(120) NULL,
  postal_code VARCHAR(30) NULL,
  country_code CHAR(2) NULL,
  avatar_url VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

/* =============================================
   PASSWORD RESETS
   ============================================= */
CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE INDEX idx_pwreset_user_expires ON password_resets(user_id, expires_at);

/* =============================================
   SESSIONS (OPTIONAL)
   ============================================= */
CREATE TABLE IF NOT EXISTS sessions (
  id CHAR(128) PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  payload MEDIUMBLOB NOT NULL,
  last_activity INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE INDEX idx_sessions_user ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

/* =============================================
   ACTIVITY LOG
   ============================================= */
CREATE TABLE IF NOT EXISTS activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  event VARCHAR(100) NOT NULL,
  description TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_event ON activity_log(event);

/* =============================================
   EMAIL VERIFICATIONS (OPTIONAL)
   ============================================= */
CREATE TABLE IF NOT EXISTS email_verifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  verified_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_emailverify_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE INDEX idx_emailverify_user_expires ON email_verifications(user_id, expires_at);

/* =============================================
   SEED ADMIN USER (UPDATE HASH BEFORE PRODUCTION)
   ============================================= */
INSERT INTO users (role_id, email, username, password_hash, status, email_verified_at)
SELECT r.id, 'admin@example.com', 'admin', '$2y$10$REPLACE_WITH_BCRYPT_HASH', 'active', NOW()
FROM roles r
WHERE r.name='admin'
  AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email='admin@example.com');

-- To generate a real password hash in PHP:
-- <?php echo password_hash('ChangeThisAdminPass!', PASSWORD_BCRYPT); ?>
