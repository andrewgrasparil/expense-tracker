# Expense Tracker

A web-based CRUD application for tracking personal expenses.
Final project for **Web Systems Technologies (PHP and MySQL Development)**.

Built with **PHP (PDO)**, **MySQL**, **HTML**, **CSS**, and **Bootstrap 5**.
Hosted on **InfinityFree**.

- Live site: `PASTE YOUR INFINITYFREE URL HERE`

## Features

- Register, log in, and log out (passwords hashed with `password_hash`)
- **Create, Read, Update, Delete** expenses
- Dashboard with total spent, record count, and this month's total
- Filter by category and search by keyword
- Export the current view to **CSV** (opens in Excel)
- Responsive Bootstrap 5 layout (navbar, cards, tables, forms)

## Security

- Prepared statements (PDO) against SQL injection
- `htmlspecialchars` output escaping against XSS
- CSRF tokens on every form
- Each user can only see and change their own expenses

## Database

Two tables, created by `database.sql`:

| Table | Fields |
| --- | --- |
| `users` | id, username, password |
| `expenses` | id, user_id, title, amount, category, expense_date, description |

## Run locally (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this folder to `C:\xampp\htdocs\expense-tracker`.
3. Open `http://localhost/phpmyadmin`, click **Import**, and import `database.sql`.
4. Open `http://localhost/expense-tracker/register.php`, create an account, and log in.

## Deploy to InfinityFree

1. Create a hosting account and a MySQL database in the InfinityFree control panel.
2. Put the database host, name, username, and password in the LIVE section of `db_connect.php`.
3. Upload the project files to `htdocs/` with the File Manager. Do not upload `database.sql`.
4. Import `database.sql` into your InfinityFree database with phpMyAdmin. Remove the `CREATE DATABASE` and `USE` lines first.

## Project structure

```
db_connect.php   database connection (PDO)
database.sql     table definitions
index.php        dashboard (READ)
create.php       add expense (CREATE)
edit.php         edit expense (UPDATE)
delete.php       delete expense (DELETE)
login.php  register.php  logout.php
export.php       CSV export
includes/        header, footer, helper functions
assets/css/      custom styles
```
