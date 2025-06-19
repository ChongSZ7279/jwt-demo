<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// JWT Middleware
$jwtMiddleware = function ($request, $handler) {
    $token = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Token required']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    try {
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        $request = $request->withAttribute('user', $decoded);
        return $handler->handle($request);
    } catch (Exception $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Invalid token']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
};

// Authentication Routes
$app->post('/login', function ($request, $response) {
    $data = json_decode((string)$request->getBody(), true);
    $username = $data['username'];
    $password = $data['password'];

    $stmt = $this->get('db')->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && ($password == $user['password'])) {
        $payload = [
            'sub' => $user['id'],
            'username' => $user['username'],
            'iat' => time(),
            'exp' => time() + 3600
        ];
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
        $response->getBody()->write(json_encode(['token' => $token, 'user' => ['id' => $user['id'], 'username' => $user['username']]]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
});

$app->post('/logout', function ($request, $response) {
    // In a real application, you might want to blacklist the token
    $response->getBody()->write(json_encode(['message' => 'Logged out successfully']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Inventory Routes (Protected with JWT)
$app->get('/inventory', function ($request, $response) use ($jwtMiddleware) {
    try {
        $request = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });
        
        $stmt = $this->get('db')->prepare("SELECT * FROM inventory ORDER BY created_at DESC");
        $stmt->execute();
        $items = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode($items));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/inventory/{id}', function ($request, $response, $args) use ($jwtMiddleware) {
    try {
        $request = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });
        
        $id = $args['id'];
        $stmt = $this->get('db')->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($item));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/inventory', function ($request, $response) use ($jwtMiddleware) {
    try {
        $request = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });
        
        $data = json_decode((string)$request->getBody(), true);
        
        $stmt = $this->get('db')->prepare("INSERT INTO inventory (name, description, quantity, price, category, sku) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['quantity'],
            $data['price'],
            $data['category'],
            $data['sku']
        ]);
        
        $id = $this->get('db')->lastInsertId();
        $response->getBody()->write(json_encode(['id' => $id, 'message' => 'Item created successfully']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});

$app->put('/inventory/{id}', function ($request, $response, $args) use ($jwtMiddleware) {
    try {
        $request = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        $id = $args['id'];
        $data = json_decode((string)$request->getBody(), true);

        // Debug: Log the received data
        error_log("Received data: " . print_r($data, true));

        // Check if JSON parsing failed
        if ($data === null) {
            $response->getBody()->write(json_encode(['error' => 'Invalid JSON data']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate required fields with better checking
        $requiredFields = ['name', 'description', 'quantity', 'price', 'category', 'sku'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "Field '$field' is missing"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // For string fields, check if empty after trimming
            if (in_array($field, ['name', 'description', 'category', 'sku'])) {
                if (trim((string)$data[$field]) === '') {
                    $response->getBody()->write(json_encode(['error' => "Field '$field' cannot be empty"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            // For numeric fields, check if they exist and are not null
            if (in_array($field, ['quantity', 'price'])) {
                if ($data[$field] === null || $data[$field] === '') {
                    $response->getBody()->write(json_encode(['error' => "Field '$field' must be a number"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }
        }

        // Validate data types
        if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
            $response->getBody()->write(json_encode(['error' => 'Quantity must be a non-negative number']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!is_numeric($data['price']) || $data['price'] < 0) {
            $response->getBody()->write(json_encode(['error' => 'Price must be a non-negative number']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Check if item exists first
        $checkStmt = $this->get('db')->prepare("SELECT id FROM inventory WHERE id = ?");
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Update the item
        $stmt = $this->get('db')->prepare("UPDATE inventory SET name = ?, description = ?, quantity = ?, price = ?, category = ?, sku = ? WHERE id = ?");
        $result = $stmt->execute([
            trim($data['name']),
            trim($data['description']),
            intval($data['quantity']),
            floatval($data['price']),
            trim($data['category']),
            trim($data['sku']),
            $id
        ]);

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Failed to update item']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['message' => 'Item updated successfully']));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        // Handle database errors
        if ($e->getCode() == 23000) { // Duplicate entry error
            $response->getBody()->write(json_encode(['error' => 'SKU already exists']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        // Handle JWT and other errors
        if (strpos($e->getMessage(), 'token') !== false || strpos($e->getMessage(), 'JWT') !== false) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->delete('/inventory/{id}', function ($request, $response, $args) use ($jwtMiddleware) {
    try {
        $request = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });
        
        $id = $args['id'];
        $stmt = $this->get('db')->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(['message' => 'Item deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/inventory/search/{query}', function ($request, $response, $args) use ($jwtMiddleware) {
    try {
        $request = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });
        
        $query = '%' . $args['query'] . '%';
        $stmt = $this->get('db')->prepare("SELECT * FROM inventory WHERE name LIKE ? OR description LIKE ? OR category LIKE ? OR sku LIKE ?");
        $stmt->execute([$query, $query, $query, $query]);
        $items = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode($items));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});
