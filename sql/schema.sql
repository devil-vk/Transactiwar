CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone_number VARCHAR(10) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  bio TEXT NULL,
  profile_image VARCHAR(255) NULL,
  balance_paise BIGINT UNSIGNED NOT NULL DEFAULT 10000000,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_username (username),
  INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id INT UNSIGNED NOT NULL,
  receiver_id INT UNSIGNED NOT NULL,
  amount_paise BIGINT UNSIGNED NOT NULL,
  comment VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_transactions_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_transactions_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT chk_sender_not_receiver CHECK (sender_id <> receiver_id),
  INDEX idx_transactions_sender (sender_id, created_at),
  INDEX idx_transactions_receiver (receiver_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  username VARCHAR(50) NOT NULL,
  page_accessed VARCHAR(255) NOT NULL,
  action VARCHAR(100) NOT NULL,
  client_ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_logs_user_created (user_id, created_at),
  INDEX idx_logs_action_created (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
