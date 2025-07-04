<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Cleanup function for expired blacklisted tokens
function cleanupExpiredTokens($db) {
    try {
        $currentTimestamp = time();
        $stmt = $db->prepare("DELETE FROM token_blacklist WHERE expires_timestamp <= ?");
        $stmt->execute([$currentTimestamp]);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Token cleanup error: " . $e->getMessage());
        return 0;
    }
}

// JWT Middleware factory function
function createJwtMiddleware($container) {
    return function ($request, $handler) use ($container) {
    $token = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Token required']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    try {
        // Decode the token first to get the claims
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

        // Check if token is blacklisted
        $db = $container->get('db');

            // Periodically cleanup expired tokens (10% chance on each request)
            if (rand(1, 100) <= 10) {
                cleanupExpiredTokens($db);
            }

            $tokenHash = hash('sha256', $token);

        // Check blacklist using token hash (use UNIX timestamps for consistency)
        $currentTimestamp = time();
        $stmt = $db->prepare("SELECT id FROM token_blacklist WHERE token_hash = ? AND expires_timestamp > ?");
        $stmt->execute([$tokenHash, $currentTimestamp]);

        if ($stmt->fetch()) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token has been invalidated']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // If we have a jti claim, also check by jti for additional security
        if (isset($decoded->jti)) {
            $stmt = $db->prepare("SELECT id FROM token_blacklist WHERE token_jti = ? AND expires_timestamp > ?");
            $stmt->execute([$decoded->jti, $currentTimestamp]);

            if ($stmt->fetch()) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'Token has been invalidated']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        }

        $request = $request->withAttribute('user', $decoded);
        $request = $request->withAttribute('token', $token); // Store token for logout
        return $handler->handle($request);
    } catch (Exception $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Invalid token: ' . $e->getMessage()]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    };
}

// Create the JWT middleware instance (will be used in routes)
$jwtMiddleware = null; // Will be set in each route that needs it

// Authentication Routes
$app->post('/login', function ($request, $response) {
    $data = json_decode((string)$request->getBody(), true);
    $username = $data['username'];
    $password = $data['password'];

    $stmt = $this->get('db')->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && ($password == $user['password'])) {
        // Generate unique JWT ID for token tracking
        $jti = uniqid('jwt_', true) . '_' . $user['id'] . '_' . time();

        $payload = [
            'sub' => $user['id'],
            'username' => $user['username'],
            'iat' => time(),
            'exp' => time() + 3600,
            'jti' => $jti  // JWT ID for token identification
        ];
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
        $response->getBody()->write(json_encode(['token' => $token, 'user' => ['id' => $user['id'], 'username' => $user['username']]]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
});

$app->post('/logout', function ($request, $response) {
    try {
        // Create JWT middleware with container access
        $jwtMiddleware = createJwtMiddleware($this);

        // Use JWT middleware to validate and extract token information
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        // Check if middleware returned an error response
        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult; // Return the error response from middleware
        }

        $request = $middlewareResult; // Middleware returned the modified request
        $user = $request->getAttribute('user');
        $token = $request->getAttribute('token');

        if (!$user || !$token) {
            $response->getBody()->write(json_encode(['error' => 'Invalid token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Add token to blacklist
        $db = $this->get('db');
        $tokenHash = hash('sha256', $token);
        $jti = isset($user->jti) ? $user->jti : null;
        $userId = $user->sub;
        $expiresAt = gmdate('Y-m-d H:i:s', $user->exp);

        // Insert into blacklist
        $stmt = $db->prepare("
            INSERT INTO token_blacklist (token_jti, token_hash, user_id, expires_at, expires_timestamp, reason)
            VALUES (?, ?, ?, ?, ?, 'logout')
            ON DUPLICATE KEY UPDATE
                blacklisted_at = CURRENT_TIMESTAMP,
                reason = 'logout'
        ");
        $stmt->execute([$jti, $tokenHash, $userId, $expiresAt, $user->exp]);

        $response->getBody()->write(json_encode([
            'message' => 'Logged out successfully',
            'token_invalidated' => true
        ]));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        // If token validation fails, still return success (user is effectively logged out)
        $response->getBody()->write(json_encode([
            'message' => 'Logged out successfully',
            'note' => 'Token was already invalid'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
});

// Inventory Routes (Protected with JWT)
$app->get('/inventory', function ($request, $response) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;
        
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

$app->get('/inventory/{id}', function ($request, $response, $args) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;
        
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

$app->post('/inventory', function ($request, $response) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;
        
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

$app->put('/inventory/{id}', function ($request, $response, $args) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;

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

$app->delete('/inventory/{id}', function ($request, $response, $args) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;
        
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

$app->get('/inventory/search/{query}', function ($request, $response, $args) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;

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

// Admin Routes for Token Management
$app->post('/admin/cleanup-tokens', function ($request, $response) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;

        $db = $this->get('db');
        $deletedCount = cleanupExpiredTokens($db);

        $response->getBody()->write(json_encode([
            'message' => 'Token cleanup completed',
            'deleted_tokens' => $deletedCount
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/admin/token-stats', function ($request, $response) {
    try {
        $jwtMiddleware = createJwtMiddleware($this);
        $middlewareResult = $jwtMiddleware($request, new class {
            public function handle($request) { return $request; }
        });

        if ($middlewareResult instanceof \Slim\Psr7\Response) {
            return $middlewareResult;
        }
        $request = $middlewareResult;

        $db = $this->get('db');

        // Get blacklist statistics
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_blacklisted,
                COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_blacklisted,
                COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_blacklisted
            FROM token_blacklist
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        $response->getBody()->write(json_encode([
            'blacklist_stats' => $stats,
            'cleanup_recommendation' => $stats['expired_blacklisted'] > 100 ? 'Consider running cleanup' : 'No cleanup needed'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});
