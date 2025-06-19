# Inventory Management System

A modern inventory management system built with Vue.js frontend and Slim PHP backend with JWT authentication.

## Features

- 🔐 **JWT Authentication** - Secure login/logout system
- 📦 **Inventory Management** - Full CRUD operations for inventory items
- 🔍 **Search & Filter** - Real-time search functionality
- 📱 **Responsive Design** - Modern UI with Tailwind CSS
- 🚀 **Modern Stack** - Vue 3, Vite, Tailwind CSS, Slim PHP

## Backend API Endpoints

### Authentication
- `POST /login` - User login
- `POST /logout` - User logout

### Inventory Management
- `GET /inventory` - Get all inventory items
- `GET /inventory/{id}` - Get specific item
- `POST /inventory` - Create new item
- `PUT /inventory/{id}` - Update item
- `DELETE /inventory/{id}` - Delete item
- `GET /inventory/search/{query}` - Search items

## Setup Instructions

### Prerequisites
- PHP 8.0+
- MySQL/MariaDB
- Node.js 16+
- Composer
- XAMPP (or similar local server)

### Backend Setup

1. **Database Setup**
   ```sql
   -- Run the schema.sql file in your MySQL database
   mysql -u root -p < schema.sql
   ```

2. **Environment Configuration**
   Create a `.env` file in the `backend` directory:
   ```env
   DB_HOST=localhost
   DB_NAME=jwt_demo
   DB_USER=root
   DB_PASS=your_password
   JWT_SECRET=your_secret_key_here
   ```

3. **Install Dependencies**
   ```bash
   cd backend
   composer install
   ```

4. **Start Backend Server**
   ```bash
   cd backend/public
   php -S localhost:8000
   ```

### Frontend Setup

1. **Install Dependencies**
   ```bash
   cd frontend
   npm install
   ```

2. **Start Development Server**
   ```bash
   npm run dev
   ```

3. **Build for Production**
   ```bash
   npm run build
   ```

## Usage

1. **Access the Application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000

2. **Login Credentials**
   - Username: `testuser`
   - Password: `password`

3. **Features**
   - **Login/Logout**: Secure authentication system
   - **View Inventory**: See all items in a responsive table
   - **Add Items**: Create new inventory items with form validation
   - **Edit Items**: Update existing items
   - **Delete Items**: Remove items with confirmation
   - **Search**: Real-time search across all item fields
   - **Responsive**: Works on desktop, tablet, and mobile

## Project Structure

```
jwt-demo/
├── backend/
│   ├── public/
│   │   └── index.php          # Entry point
│   ├── src/
│   │   ├── dependencies.php   # Database configuration
│   │   └── routes.php         # API routes
│   ├── vendor/                # Composer dependencies
│   └── composer.json
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   │   ├── Login.vue      # Login component
│   │   │   ├── Dashboard.vue  # Main layout
│   │   │   ├── InventoryList.vue  # Inventory table
│   │   │   └── InventoryForm.vue  # Add/Edit form
│   │   ├── services/
│   │   │   └── api.js         # API service
│   │   ├── App.vue            # Root component
│   │   ├── main.js            # Entry point
│   │   └── style.css          # Global styles
│   ├── index.html
│   ├── package.json
│   └── vite.config.js
├── schema.sql                 # Database schema
└── README.md
```

## Technologies Used

### Backend
- **Slim Framework** - PHP micro-framework
- **Firebase JWT** - JWT token handling
- **PDO** - Database abstraction
- **Composer** - Dependency management

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Vue Router** - Client-side routing
- **Axios** - HTTP client
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Build tool and dev server

## Security Features

- JWT token-based authentication
- Protected API endpoints
- Input validation
- SQL injection prevention with prepared statements
- CORS handling
- Secure password handling

## Development

### Adding New Features
1. Create new routes in `backend/src/routes.php`
2. Add corresponding API methods in `frontend/src/services/api.js`
3. Create Vue components in `frontend/src/components/`
4. Update routing in `frontend/src/main.js`

### Styling
- Uses Tailwind CSS utility classes
- Custom components defined in `frontend/src/style.css`
- Responsive design with mobile-first approach

## Troubleshooting

### Common Issues

1. **CORS Errors**
   - Ensure backend is running on port 8000
   - Check Vite proxy configuration

2. **Database Connection**
   - Verify database credentials in `.env`
   - Ensure MySQL service is running

3. **JWT Token Issues**
   - Check JWT_SECRET in `.env`
   - Verify token expiration settings

4. **Frontend Build Issues**
   - Clear node_modules and reinstall: `rm -rf node_modules && npm install`
   - Check Node.js version compatibility

## License

This project is open source and available under the MIT License. 