CREATE TABLE IF NOT EXISTS pairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pair_id INT NOT NULL,
    date DATE NOT NULL,
    type ENUM('positive', 'negative') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pair_id) REFERENCES pairs(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_pair_date_type (pair_id, date, type)
);
