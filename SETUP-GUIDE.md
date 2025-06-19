# Complete Setup Guide - Inventory Management System

## 🚀 Quick Start

### Step 1: Database Setup
1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Create a new database called `jwt_demo`
3. Import the `schema.sql` file into this database

### Step 2: Backend Setup
1. **Run the setup script:**
   ```bash
   setup-backend.bat
   ```
   
   OR manually:
   ```bash
   cd backend
   composer install
   ```

2. **Create .env file** in `backend/` directory:
   ```env
   DB_HOST=localhost
   DB_NAME=jwt_demo
   DB_USER=root
   DB_PASS=
   JWT_SECRET=inventory_management_system_secret_key_2024
   ```

3. **Start backend server:**
   ```bash
   cd backend/public
   php -S localhost:8000
   ```

### Step 3: Frontend Setup
1. **Install dependencies:**
   ```bash
   cd frontend
   npm install
   ```

2. **Start frontend server:**
   ```bash
   npm run dev
   ```

### Step 4: Access Application
- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8000
- **Login:** `testuser` / `password`

## 🔧 Troubleshooting

### If "Add Item" doesn't work:

1. **Check Browser Console** (F12):
   - Look for any error messages
   - Check if API requests are being made

2. **Check Backend Server:**
   - Make sure it's running on port 8000
   - Check for any PHP errors

3. **Test API directly:**
   ```bash
   # Test login
   curl -X POST http://localhost:8000/login \
     -H "Content-Type: application/json" \
     -d '{"username":"testuser","password":"password"}'
   ```

4. **Check Database:**
   - Verify `jwt_demo` database exists
   - Check if `users` and `inventory` tables exist
   - Ensure sample data is imported

### Common Issues:

1. **CORS Errors:**
   - Backend has CORS headers added
   - Frontend proxy is configured correctly

2. **Database Connection:**
   - Check `.env` file exists in backend directory
   - Verify database credentials
   - Ensure MySQL service is running

3. **JWT Token Issues:**
   - Check JWT_SECRET in `.env`
   - Verify token is being sent in Authorization header

## 📁 File Structure
```
jwt-demo/
├── backend/
│   ├── .env                    # Database configuration
│   ├── public/
│   │   └── index.php          # Entry point
│   ├── src/
│   │   ├── dependencies.php   # Database setup
│   │   └── routes.php         # API routes
│   └── composer.json
├── frontend/
│   ├── src/
│   │   ├── components/        # Vue components
│   │   ├── services/
│   │   │   └── api.js         # API service
│   │   └── main.js            # Entry point
│   ├── package.json
│   └── vite.config.js
├── schema.sql                 # Database schema
├── setup-backend.bat          # Windows setup script
└── README.md
```

## 🎯 Testing the System

1. **Login Test:**
   - Go to http://localhost:3000
   - Login with `testuser` / `password`
   - Should redirect to dashboard

2. **Inventory List Test:**
   - Should show sample inventory items
   - Search functionality should work

3. **Add Item Test:**
   - Click "Add Item" button
   - Fill out the form
   - Submit and check if item appears in list

4. **Edit/Delete Test:**
   - Click "Edit" on any item
   - Modify and save
   - Click "Delete" to remove item

## 🔍 Debug Mode

The frontend now includes console logging:
- Open browser console (F12)
- All API requests/responses are logged
- Check for any error messages

## 📞 Support

If you're still having issues:
1. Check browser console for errors
2. Verify both servers are running
3. Test API endpoints directly
4. Check database connection 