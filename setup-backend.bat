@echo off
echo Setting up Backend Environment...
echo.

cd backend

echo Creating .env file...
if not exist .env (
    echo DB_HOST=localhost > .env
    echo DB_NAME=jwt_demo >> .env
    echo DB_USER=root >> .env
    echo DB_PASS= >> .env
    echo JWT_SECRET=inventory_management_system_secret_key_2024 >> .env
    echo .env file created successfully!
) else (
    echo .env file already exists
)

echo.
echo Installing PHP dependencies...
composer install

echo.
echo Backend setup completed!
echo.
echo Next steps:
echo 1. Import schema.sql into your MySQL database
echo 2. Start the backend server: cd backend/public ^&^& php -S localhost:8000
echo 3. Start the frontend server: cd frontend ^&^& npm run dev
echo.
echo Database setup:
echo - Open phpMyAdmin or MySQL command line
echo - Create database: CREATE DATABASE jwt_demo;
echo - Import schema.sql file
echo.
pause 