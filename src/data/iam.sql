CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'editor',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password_hash, role)
VALUES (
  'admin',
  -- PHP: password_hash('passwort123', PASSWORD_DEFAULT)
  '$2y$10$nX8wY3nH64jM4uIlMxMKRehVmhkEmJbRjsRAoW2KOSv6HwH/MEn5C',
  'Admin'
);