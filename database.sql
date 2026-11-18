-- ================================================================
-- database.sql  |  Expense Tracker
-- ================================================================
-- HOW TO USE
--   XAMPP (local):  open phpMyAdmin > Import > choose this file > Go.
--                   The two lines in PART 1 create the database for you.
--
--   InfinityFree:   you cannot create a database with SQL there.
--                   1) Create the database in the Control Panel first.
--                   2) In phpMyAdmin, click YOUR database on the left.
--                   3) DELETE PART 1 (the CREATE DATABASE and USE lines)
--                      from a copy of this file, then Import it.
-- ================================================================

-- ---------------------- PART 1: XAMPP ONLY ----------------------
CREATE DATABASE IF NOT EXISTS expense_tracker
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE expense_tracker;
-- ------------------------- END OF PART 1 -------------------------


-- ---------------------- PART 2: TABLES --------------------------

-- Table 1: users
-- Stores the accounts. The password column holds a bcrypt HASH
-- (about 60 characters), so VARCHAR(255) leaves room for the future.
CREATE TABLE IF NOT EXISTS users (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50)  NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)   -- no two users share a username
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: expenses  (7 fields)
-- Each expense belongs to one user (user_id -> users.id).
-- ON DELETE CASCADE: if a user is deleted, their expenses go too.
CREATE TABLE IF NOT EXISTS expenses (
  id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED  NOT NULL,
  title        VARCHAR(100)  NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,        -- money: exact decimals, never FLOAT
  category     VARCHAR(50)   NOT NULL,
  expense_date DATE          NOT NULL,
  description  TEXT          NULL,            -- optional note
  PRIMARY KEY (id),
  KEY idx_expenses_user_date (user_id, expense_date),
  CONSTRAINT fk_expenses_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
