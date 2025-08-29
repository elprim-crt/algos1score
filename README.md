# algos1score

A lightweight PHP-based backend for recording and reporting trades with positive/negative outcomes, designed for simplicity and easy integration with frontend clients.

## Features

- **Add Trades:** Record trades for specific pairs with type (`positive` or `negative`) and date.
- **14-Day Reporting:** Get the count of positive or negative trades per pair for the last 14 days.
- **RESTful API:** Simple POST-based API for integration.
- **CSRF Protection:** All POST requests require a valid CSRF token stored in the user session.

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
  "date": "2025-08-29",
  "csrf_token": "<token from session>"
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

## CSRF Tokens

Every POST request must include a `csrf_token` value that matches the token stored in the current session. The main page embeds this token in the "Add Pair" form and exposes it to JavaScript for API calls.

## Database Setup

You need two tables: `pairs` (for trade pairs) and `trades` (for trade entries).

```sql
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
  FOREIGN KEY (pair_id) REFERENCES pairs(id) ON DELETE CASCADE
);
```

## Configuration

Database credentials are read from environment variables. Set the following values in your environment or in a `.env` file:

```bash
DB_HOST=localhost
DB_NAME=algos1score
DB_USER=your_db_user
DB_PASS=your_db_password
```

If you're using [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv), copy `.env.example` to `.env` and adjust the values as needed.

## Debug Logging

`debug.php` provides a `debug_log()` helper that writes messages to `debug.log` in the project root. Each entry in the log includes a timestamp.

To record a message:

```php
require 'debug.php';
debug_log('Something happened');
```

To watch log output in real time:

```bash
tail -f debug.log
```

Each log entry looks like:

```
[2025-08-30 12:34:56] Something happened
```

Use these timestamps and messages to trace application flow and troubleshoot issues.

## License

MIT License

---

> **Maintainer:** [elprim-crt](https://github.com/elprim-crt)
