# API Documentation - Inventory Management System

## Base URL
```
http://localhost:8000
```

## Authentication
All inventory endpoints require JWT authentication. Include the token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## API Endpoints

| Method | Endpoint | Description | Request Body | Response | Status Codes |
|--------|----------|-------------|--------------|----------|--------------|
| **POST** | `/login` | User authentication | `{"username": "string", "password": "string"}` | `{"token": "string", "user": {"id": number, "username": "string"}}` | 200, 401 |
| **POST** | `/logout` | User logout | None | `{"message": "Logged out successfully"}` | 200 |
| **GET** | `/inventory` | Get all inventory items | None | `[{"id": number, "name": "string", "description": "string", "quantity": number, "price": number, "category": "string", "sku": "string", "created_at": "string", "updated_at": "string"}]` | 200, 401 |
| **GET** | `/inventory/{id}` | Get specific inventory item | None | `{"id": number, "name": "string", "description": "string", "quantity": number, "price": number, "category": "string", "sku": "string", "created_at": "string", "updated_at": "string"}` | 200, 401, 404 |
| **POST** | `/inventory` | Create new inventory item | `{"name": "string", "description": "string", "quantity": number, "price": number, "category": "string", "sku": "string"}` | `{"id": number, "message": "Item created successfully"}` | 201, 401 |
| **PUT** | `/inventory/{id}` | Update existing inventory item | `{"name": "string", "description": "string", "quantity": number, "price": number, "category": "string", "sku": "string"}` | `{"message": "Item updated successfully"}` | 200, 401, 404 |
| **DELETE** | `/inventory/{id}` | Delete inventory item | None | `{"message": "Item deleted successfully"}` | 200, 401, 404 |
| **GET** | `/inventory/search/{query}` | Search inventory items | None | `[{"id": number, "name": "string", "description": "string", "quantity": number, "price": number, "category": "string", "sku": "string", "created_at": "string", "updated_at": "string"}]` | 200, 401 |

## Detailed Endpoint Descriptions

### Authentication Endpoints

#### POST /login
Authenticates a user and returns a JWT token.

**Request Body:**
```json
{
  "username": "testuser",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "testuser"
  }
}
```

**Error Response (401):**
```json
{
  "error": "Invalid credentials"
}
```

#### POST /logout
Logs out the current user (token invalidation).

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

### Inventory Management Endpoints

#### GET /inventory
Retrieves all inventory items, ordered by creation date (newest first).

**Headers Required:**
```
Authorization: Bearer <jwt_token>
```

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "Laptop Dell XPS 13",
    "description": "13-inch premium laptop with Intel i7 processor",
    "quantity": 15,
    "price": "1299.99",
    "category": "Electronics",
    "sku": "LAP-DELL-XPS13",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
]
```

#### GET /inventory/{id}
Retrieves a specific inventory item by ID.

**Path Parameters:**
- `id` (number): The inventory item ID

**Success Response (200):**
```json
{
  "id": 1,
  "name": "Laptop Dell XPS 13",
  "description": "13-inch premium laptop with Intel i7 processor",
  "quantity": 15,
  "price": "1299.99",
  "category": "Electronics",
  "sku": "LAP-DELL-XPS13",
  "created_at": "2024-01-15 10:30:00",
  "updated_at": "2024-01-15 10:30:00"
}
```

**Error Response (404):**
```json
{
  "error": "Item not found"
}
```

#### POST /inventory
Creates a new inventory item.

**Request Body:**
```json
{
  "name": "New Item Name",
  "description": "Item description",
  "quantity": 10,
  "price": 99.99,
  "category": "Electronics",
  "sku": "ITEM-SKU-001"
}
```

**Success Response (201):**
```json
{
  "id": 6,
  "message": "Item created successfully"
}
```

#### PUT /inventory/{id}
Updates an existing inventory item.

**Path Parameters:**
- `id` (number): The inventory item ID

**Request Body:**
```json
{
  "name": "Updated Item Name",
  "description": "Updated description",
  "quantity": 20,
  "price": 149.99,
  "category": "Accessories",
  "sku": "ITEM-SKU-002"
}
```

**Success Response (200):**
```json
{
  "message": "Item updated successfully"
}
```

#### DELETE /inventory/{id}
Deletes an inventory item.

**Path Parameters:**
- `id` (number): The inventory item ID

**Success Response (200):**
```json
{
  "message": "Item deleted successfully"
}
```

#### GET /inventory/search/{query}
Searches inventory items by name, description, category, or SKU.

**Path Parameters:**
- `query` (string): Search term

**Success Response (200):**
```json
[
  {
    "id": 1,
    "name": "Laptop Dell XPS 13",
    "description": "13-inch premium laptop with Intel i7 processor",
    "quantity": 15,
    "price": "1299.99",
    "category": "Electronics",
    "sku": "LAP-DELL-XPS13",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
]
```

## Error Responses

### Authentication Errors (401)
```json
{
  "error": "Token required"
}
```
```json
{
  "error": "Invalid token"
}
```
```json
{
  "error": "Unauthorized"
}
```

### Not Found Errors (404)
```json
{
  "error": "Item not found"
}
```

## Data Models

### Inventory Item
```json
{
  "id": "number (auto-increment)",
  "name": "string (required)",
  "description": "string (optional)",
  "quantity": "number (required, min: 0)",
  "price": "decimal (required, min: 0)",
  "category": "string (required)",
  "sku": "string (required, unique)",
  "created_at": "timestamp (auto-generated)",
  "updated_at": "timestamp (auto-updated)"
}
```

### User
```json
{
  "id": "number (auto-increment)",
  "username": "string (required, unique)",
  "password": "string (required)"
}
```

## Testing with cURL

### Login
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"password"}'
```

### Get All Items (with token)
```bash
curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Create Item
```bash
curl -X POST http://localhost:8000/inventory \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "name": "Test Item",
    "description": "Test description",
    "quantity": 5,
    "price": 29.99,
    "category": "Electronics",
    "sku": "TEST-001"
  }'
```

## Notes

- All timestamps are in MySQL DATETIME format
- Prices are stored as DECIMAL(10,2) in the database
- SKU must be unique across all inventory items
- JWT tokens expire after 1 hour (3600 seconds)
- All inventory endpoints require valid JWT authentication 