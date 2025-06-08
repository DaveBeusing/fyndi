CREATE TABLE iam_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'editor',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO iam_users (username, password_hash, role)
VALUES (
  'admin',
  -- PHP: password_hash('passwort123', PASSWORD_DEFAULT)
  '$2y$10$nX8wY3nH64jM4uIlMxMKRehVmhkEmJbRjsRAoW2KOSv6HwH/MEn5C',
  'Admin'
);

CREATE TABLE iam_logins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uid INT NOT NULL,
  success TINYINT(1) NOT NULL,
  ip_address BIGINT UNSIGNED,
  user_agent VARCHAR(255),
  login_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;