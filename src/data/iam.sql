
INSERT INTO iam_users ( email, password_hash, role )
VALUES (
  'david.beusing@gmail.com',
  -- PHP: password_hash('passwort123', PASSWORD_DEFAULT)
  'password_hash',
  'admin'
);

CREATE TABLE `iam_users` (
  `uid` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `iam_users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `email` (`email`);

CREATE TABLE iam_logins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uid INT NOT NULL,
  success TINYINT(1) NOT NULL,
  ip_address BIGINT UNSIGNED,
  user_agent VARCHAR(255),
  login_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;