# Token Blacklist Testing Guide

## Prerequisites
1. Make sure your backend server is running: `cd backend/public && php -S localhost:8000`
2. Import the migration script: Run `migrate-blacklist.sql` in your MySQL database
3. Have a tool like Postman, curl, or similar for API testing

## Test Scenarios

### 1. Test Normal Login and Access
```bash
# Login to get a token
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"password"}'

# Save the token from the response, then test inventory access
curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 2. Test Logout and Token Invalidation
```bash
# Logout (this should blacklist the token)
curl -X POST http://localhost:8000/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Try to access inventory with the same token (should fail)
curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Test Multiple Sessions
```bash
# Login twice to get two different tokens
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"password"}'

# Save as TOKEN_1, then login again
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"password"}'

# Save as TOKEN_2
# Both tokens should work initially
curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer TOKEN_1"

curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer TOKEN_2"

# Logout with TOKEN_1
curl -X POST http://localhost:8000/logout \
  -H "Authorization: Bearer TOKEN_1"

# TOKEN_1 should now fail, but TOKEN_2 should still work
curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer TOKEN_1"  # Should fail

curl -X GET http://localhost:8000/inventory \
  -H "Authorization: Bearer TOKEN_2"  # Should work
```

### 4. Test Admin Endpoints
```bash
# Check token statistics
curl -X GET http://localhost:8000/admin/token-stats \
  -H "Authorization: Bearer YOUR_VALID_TOKEN"

# Manually cleanup expired tokens
curl -X POST http://localhost:8000/admin/cleanup-tokens \
  -H "Authorization: Bearer YOUR_VALID_TOKEN"
```

## Expected Results

### Before Logout:
- ✅ Login should return a token with `jti` field
- ✅ Inventory access should work with valid token
- ✅ Token should contain `jti` claim when decoded

### After Logout:
- ✅ Logout should return success message with `token_invalidated: true`
- ❌ Inventory access should fail with "Token has been invalidated"
- ✅ Database should contain the blacklisted token

### Database Verification:
```sql
-- Check blacklisted tokens
SELECT * FROM token_blacklist;

-- Check active vs expired blacklisted tokens
SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active,
    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired
FROM token_blacklist;
```

## Troubleshooting

### Common Issues:
1. **"Table doesn't exist"** - Run the migration script
2. **"Foreign key constraint fails"** - Make sure users table exists
3. **"Token still works after logout"** - Check if blacklist table has the entry
4. **"Cleanup not working"** - Check expires_at timestamps

### Debug Steps:
1. Check backend logs for any PHP errors
2. Verify database connection in `.env` file
3. Check if token_blacklist table was created properly
4. Verify JWT_SECRET is consistent between login and validation
