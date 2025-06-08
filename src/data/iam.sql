
INSERT INTO iam_users ( email, password_hash, role )
VALUES (
  'david.beusing@gmail.com',
  -- PHP: password_hash('passwort123', PASSWORD_DEFAULT)
  'password_hash',
  'admin'
);

CREATE TABLE iam_users (
  uid INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  status TINYINT(1) NOT NULL DEFAULT 1,
  login_attempts INT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  updated_by INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE iam_logins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uid INT NOT NULL,
  success TINYINT(1) NOT NULL,
  ip_address BIGINT UNSIGNED,
  user_agent VARCHAR(255),
  login_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;