#!/bin/bash

echo "🚀 Setting up Inventory Management System"
echo "=========================================="

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 16+ first."
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.0+ first."
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

echo "✅ Prerequisites check passed"

# Backend setup
echo ""
echo "📦 Setting up Backend..."
cd backend

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
DB_HOST=localhost
DB_NAME=jwt_demo
DB_USER=root
DB_PASS=
JWT_SECRET=inventory_management_system_secret_key_2024
EOF
    echo "⚠️  Please update the .env file with your database credentials"
fi

cd ..

# Frontend setup
echo ""
echo "🎨 Setting up Frontend..."
cd frontend

# Install Node.js dependencies
echo "Installing Node.js dependencies..."
npm install

cd ..

echo ""
echo "✅ Setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Update backend/.env with your database credentials"
echo "2. Import schema.sql into your MySQL database"
echo "3. Start the backend server: cd backend/public && php -S localhost:8000"
echo "4. Start the frontend server: cd frontend && npm run dev"
echo "5. Access the application at http://localhost:3000"
echo ""
echo "🔑 Default login credentials:"
echo "   Username: testuser"
echo "   Password: password" 