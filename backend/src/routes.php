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
        
        $stmt = $this->get('db')->prepare("UPDATE inventory SET name = ?, description = ?, quantity = ?, price = ?, category = ?, sku = ? WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['quantity'],
            $data['price'],
            $data['category'],
            $data['sku'],
            $id
        ]);
        
        if ($stmt->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Item not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(['message' => 'Item updated successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
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
