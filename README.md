# Laravel Authentication Backend

A Laravel backend with user authentication (signup and login) using API tokens via Sanctum.

## Features

- User registration (signup)
- User login with token authentication
- User logout
- Protected routes with authentication
- SQLite database

## Setup Instructions

### 1. Prerequisites

- PHP 8.1+
- PostgreSQL database running
- Composer

### 2. Install Dependencies

```bash
composer install --ignore-platform-reqs
```

### 3. Configure Environment

```bash
copy .env.example .env
```

Update the `.env` file with your PostgreSQL credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Create Database and Run Migrations

```bash
php artisan migrate
```

### 6. Start Development Server

```bash
php artisan serve
```

The server will run on `http://localhost:8000`

## API Endpoints

### Authentication

#### Sign Up
```
POST /api/auth/signup
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

Response:
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Login
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password"
}
```

Response:
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "token": "1|YOUR_AUTH_TOKEN_HERE"
}
```

#### Get Current User (Protected)
```
GET /api/auth/user
Authorization: Bearer YOUR_AUTH_TOKEN_HERE
```

Response:
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### Logout (Protected)
```
POST /api/auth/logout
Authorization: Bearer YOUR_AUTH_TOKEN_HERE
```

Response:
```json
{
  "message": "Logout successful"
}
```

## Project Structure

```
laravel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── AuthController.php
│   └── Models/
│       └── User.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000000_create_users_table.php
│       └── 2024_01_01_000001_create_personal_access_tokens_table.php
├── routes/
│   └── api.php
├── config/
│   └── database.php
├── .env.example
├── composer.json
└── README.md
```

## Technologies

- Laravel 11
- Laravel Sanctum (for API token authentication)
- PostgreSQL Database
- PHP 8.1+

## Notes

- Passwords are hashed using bcrypt
- API tokens are generated on login and can be used for subsequent requests
- Tokens should be sent in the `Authorization` header as `Bearer TOKEN`
- All passwords are automatically hidden from API responses
