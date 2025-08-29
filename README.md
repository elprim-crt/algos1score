# algos1score

A lightweight PHP-based backend for recording and reporting trades with positive/negative outcomes, designed for simplicity and easy integration with frontend clients.

## Features

- **Add Trades:** Record trades for specific pairs with type (`positive` or `negative`) and date.
- **14-Day Reporting:** Get the count of positive or negative trades per pair for the last 14 days.
- **RESTful API:** Simple POST-based API for integration.

## Files

- `config.php` – Database connection configuration.
- `db.php` – PDO-based database connection handler.
- `trades.php` – Main endpoint for adding trades and reporting.
  
## API Usage

### Add a Trade

**Endpoint:**  
`POST /trades.php`  

**Request Body (JSON):**
```json
{
  "action": "add",
  "pair_id": 1,
  "type": "positive",
  "date": "2025-08-29"
}
```

**Response:**
```json
{
  "success": true,
  "count": 2
}
```
Returns the updated count for the specified type and pair in the last 14 days.

### Errors

Error responses will include `success: false` and an error message:
```json
{
  "success": false,
  "error": "Invalid pair_id"
}
```

## Database Setup

You need two tables: `pairs` (for trade pairs) and `trades` (for trade entries).

```sql
CREATE TABLE IF NOT EXISTS pairs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pair VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS trades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pair_id INT NOT NULL,
  date DATE NOT NULL,
  type ENUM('positive', 'negative') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pair_id) REFERENCES pairs(id) ON DELETE CASCADE
);
```

## Configuration

Update `config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'algos1score');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

## License

MIT License

---

> **Maintainer:** [elprim-crt](https://github.com/elprim-crt)
