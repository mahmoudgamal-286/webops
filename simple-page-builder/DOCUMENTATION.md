# MAHMOUD GAMAL - Complete Documentation

**Author:** MAHMOUD GAMAL  
**Version:** 1.0.0

## Table of Contents
1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [API Key Management](#api-key-management)
6. [REST API Usage](#rest-api-usage)
7. [Webhook System](#webhook-system)
8. [Admin Interface](#admin-interface)
9. [Security](#security)
10. [Examples](#examples)
11. [Troubleshooting](#troubleshooting)

---

## Overview

**MAHMOUD GAMAL** is a production-ready WordPress plugin that enables bulk page creation via a secure REST API. The plugin provides advanced API key authentication, rate limiting, webhook notifications, and comprehensive logging.

**Developed by:** MAHMOUD GAMAL

### Key Capabilities
- ✅ Secure REST API endpoint accessible from external applications
- ✅ API Key + Secret authentication system
- ✅ Rate limiting per API key
- ✅ Webhook notifications with HMAC-SHA256 signature
- ✅ Complete admin interface for management
- ✅ Comprehensive activity logging

---

## Features

### 1. REST API Endpoint
- **Endpoint:** `POST /wp-json/pagebuilder/v1/create-pages`
- **External Access:** No WordPress admin login required
- **Authentication:** API Key + Secret via headers
- **Bulk Creation:** Create multiple pages in a single request

### 2. API Key Management
- Generate API keys with custom names
- Optional expiration dates
- Instant revocation
- Usage tracking and statistics
- Permissions system (create_pages)

### 3. Security Features
- API keys stored as hashed values (bcrypt)
- Rate limiting per key (default: 100 requests/hour)
- HMAC-SHA256 webhook signatures
- Request logging with IP tracking
- Global API enable/disable toggle

### 4. Webhook System
- Automatic notifications on page creation
- HMAC-SHA256 signature verification
- Retry logic with exponential backoff (5s, 25s)
- Non-blocking (doesn't affect page creation)
- Delivery status logging

### 5. Admin Interface
- API Keys Management
- API Activity Logs (with filters and CSV export)
- Created Pages listing
- Settings configuration
- API Documentation

---

## Installation

### Step 1: Upload Plugin
1. Copy the `simple-page-builder` folder to `wp-content/plugins/`
2. Ensure the folder structure is:
   ```
   wp-content/plugins/simple-page-builder/
   ├── simple-page-builder.php
   ├── includes/
   ├── admin/
   └── README.md
   ```

### Step 2: Activate Plugin
1. Go to **WordPress Admin → Plugins**
2. Find **MAHMOUD GAMAL**
3. Click **Activate**

### Step 3: Database Setup
On activation, the plugin automatically creates two database tables:
- `wp_spb_api_keys` - Stores API keys and secrets
- `wp_spb_api_logs` - Stores API request logs

---

## Configuration

### Access Settings
Navigate to **Tools → MAHMOUD GAMAL → Settings**

### Required Settings

#### 1. Webhook Configuration
- **Default Webhook URL:** Your external service endpoint
  - Example: `https://your-service.com/webhook`
- **Webhook Secret Key:** Secret for HMAC signature generation
  - Generate a strong random string (minimum 32 characters)
  - Example: `your-secret-key-here-32-chars-min`

#### 2. Rate Limiting
- **Rate Limit (Requests/Hour):** Maximum requests per API key per hour
  - Default: `100`
  - Recommended: `50-200` depending on your needs

#### 3. Global API Access
- **Enable/Disable:** Toggle API access globally
  - When disabled, all API requests return `403 Forbidden`

#### 4. Key Expiration Default
- **Default Expiration:** Default expiration period for new keys
  - Options: `30 days`, `60 days`, `90 days`, `Never`
  - Can be customized per key during generation

---

## API Key Management

### Generating API Keys

1. Navigate to **Tools → MAHMOUD GAMAL → API Keys**
2. Click **Generate New API Key**
3. Fill in the form:
   - **Key Name:** Friendly identifier (e.g., "Production Server", "Mobile App")
   - **Expiration Date:** Optional (leave empty for no expiration)
4. Click **Generate Key**

### Important: Save Your Keys
⚠️ **CRITICAL:** The API Key and Secret are shown **ONLY ONCE** after generation.

**You must:**
- Copy both values immediately
- Store them securely (password manager recommended)
- Never share them publicly

### Key Information
Each API key includes:
- **Key Name:** Friendly identifier
- **Status:** Active or Revoked
- **Created Date:** When the key was generated
- **Last Used:** Timestamp of last successful request
- **Request Count:** Total number of requests made
- **Expiration Date:** When the key expires (if set)

### Revoking Keys
1. Go to **API Keys** tab
2. Find the key you want to revoke
3. Click **Revoke**
4. The key will be immediately disabled

**Note:** Revoked keys cannot be re-enabled. Generate a new key if needed.

---

## REST API Usage

### Endpoint
```
POST /wp-json/pagebuilder/v1/create-pages
```

### Authentication
Send **BOTH** headers with every request:
- `X-API-Key: YOUR_API_KEY`
- `X-API-Secret: YOUR_API_SECRET`

**Both are required.** Missing either header results in `401 Unauthorized`.

### Request Format

#### Headers
```
Content-Type: application/json
X-API-Key: YOUR_64_CHARACTER_API_KEY
X-API-Secret: YOUR_32_CHARACTER_SECRET
```

#### Body (JSON Array)
```json
[
  {
    "title": "Page Title",
    "content": "<p>Page content with HTML</p>"
  }
]
```

### Response Format

#### Success (201 Created)
```json
{
  "status": "success",
  "created": [
    {
      "id": 123,
      "url": "https://example.com/page-title/"
    }
  ]
}
```

#### Error Responses

**401 Unauthorized** - Invalid or missing API credentials
```json
{
  "code": "spb_invalid_key",
  "message": "Invalid or expired API Key/Secret.",
  "data": {
    "status": 401
  }
}
```

**403 Forbidden** - API access disabled or insufficient permissions
```json
{
  "code": "spb_disabled",
  "message": "API access disabled.",
  "data": {
    "status": 403
  }
}
```

**429 Too Many Requests** - Rate limit exceeded
```json
{
  "code": "spb_rate_limited",
  "message": "Rate limit exceeded. Try again later.",
  "data": {
    "status": 429
  }
}
```

**400 Bad Request** - Invalid payload
```json
{
  "code": "spb_invalid_payload",
  "message": "Payload must be a non-empty JSON array of pages.",
  "data": {
    "status": 400
  }
}
```

---

## Examples

### Example 1: Create Single Page (cURL)

```bash
curl -X POST "https://example.com/wp-json/pagebuilder/v1/create-pages" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_64_CHAR_API_KEY" \
  -H "X-API-Secret: YOUR_32_CHAR_SECRET" \
  -d '[{"title":"My First Page","content":"<h1>Welcome</h1><p>This is my first page created via API.</p>"}]'
```

### Example 2: Create Multiple Pages (cURL)

```bash
curl -X POST "https://example.com/wp-json/pagebuilder/v1/create-pages" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_64_CHAR_API_KEY" \
  -H "X-API-Secret: YOUR_32_CHAR_SECRET" \
  -d '[
    {
      "title": "About Us",
      "content": "<h1>About Us</h1><p>Learn more about our company.</p>"
    },
    {
      "title": "Contact",
      "content": "<h1>Contact Us</h1><p>Get in touch with our team.</p>"
    },
    {
      "title": "Services",
      "content": "<h1>Our Services</h1><p>Discover what we offer.</p>"
    }
  ]'
```

### Example 3: Using JSON File (cURL)

**Create `pages.json`:**
```json
[
  {
    "title": "Home",
    "content": "<h1>Welcome Home</h1><p>This is the homepage.</p>"
  },
  {
    "title": "Products",
    "content": "<h1>Our Products</h1><p>Browse our product catalog.</p>"
  }
]
```

**Run cURL:**
```bash
curl -X POST "https://example.com/wp-json/pagebuilder/v1/create-pages" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_64_CHAR_API_KEY" \
  -H "X-API-Secret: YOUR_32_CHAR_SECRET" \
  -d @pages.json
```

### Example 4: PHP

```php
<?php
$api_key = 'YOUR_64_CHAR_API_KEY';
$api_secret = 'YOUR_32_CHAR_SECRET';
$endpoint = 'https://example.com/wp-json/pagebuilder/v1/create-pages';

$pages = [
    [
        'title' => 'New Page',
        'content' => '<p>Page content here</p>'
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $api_key,
    'X-API-Secret: ' . $api_secret
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pages));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 201) {
    $result = json_decode($response, true);
    echo "Success! Created " . count($result['created']) . " page(s)\n";
    foreach ($result['created'] as $page) {
        echo "  - Page ID: {$page['id']}, URL: {$page['url']}\n";
    }
} else {
    echo "Error: HTTP $http_code\n";
    echo $response . "\n";
}
?>
```

### Example 5: Python

```python
import requests
import json

api_key = 'YOUR_64_CHAR_API_KEY'
api_secret = 'YOUR_32_CHAR_SECRET'
endpoint = 'https://example.com/wp-json/pagebuilder/v1/create-pages'

pages = [
    {
        'title': 'Python Page',
        'content': '<p>This page was created using Python.</p>'
    }
]

headers = {
    'Content-Type': 'application/json',
    'X-API-Key': api_key,
    'X-API-Secret': api_secret
}

response = requests.post(endpoint, headers=headers, json=pages)

if response.status_code == 201:
    result = response.json()
    print(f"Success! Created {len(result['created'])} page(s)")
    for page in result['created']:
        print(f"  - Page ID: {page['id']}, URL: {page['url']}")
else:
    print(f"Error: HTTP {response.status_code}")
    print(response.text)
```

### Example 6: JavaScript (Node.js)

```javascript
const axios = require('axios');

const apiKey = 'YOUR_64_CHAR_API_KEY';
const apiSecret = 'YOUR_32_CHAR_SECRET';
const endpoint = 'https://example.com/wp-json/pagebuilder/v1/create-pages';

const pages = [
    {
        title: 'Node.js Page',
        content: '<p>This page was created using Node.js.</p>'
    }
];

axios.post(endpoint, pages, {
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey,
        'X-API-Secret': apiSecret
    }
})
.then(response => {
    if (response.status === 201) {
        const result = response.data;
        console.log(`Success! Created ${result.created.length} page(s)`);
        result.created.forEach(page => {
            console.log(`  - Page ID: ${page.id}, URL: ${page.url}`);
        });
    }
})
.catch(error => {
    console.error('Error:', error.response?.status, error.response?.data);
});
```

---

## Webhook System

### Overview
When a page is created via the API, the plugin automatically sends a POST request to your configured webhook URL.

### Webhook Payload
```json
{
  "page_id": 123,
  "title": "Page Title",
  "url": "https://example.com/page-title/",
  "key_name": "Production Server",
  "timestamp": "2025-12-17T12:00:00+00:00"
}
```

### Security: Signature Verification

The plugin includes an `X-Webhook-Signature` header with each webhook request. Verify this signature to ensure the request is authentic.

#### Signature Generation
The signature is computed as:
```
HMAC-SHA256(JSON_PAYLOAD, WEBHOOK_SECRET)
```

#### Verification Example (PHP)
```php
<?php
$webhook_secret = 'your-webhook-secret-key';
$raw_body = file_get_contents('php://input');
$header_sig = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

$calculated_sig = hash_hmac('sha256', $raw_body, $webhook_secret);

if (!hash_equals($calculated_sig, $header_sig)) {
    http_response_code(401);
    exit('Invalid signature');
}

// Signature is valid, process the webhook
$data = json_decode($raw_body, true);
// ... process $data
?>
```

#### Verification Example (Node.js)
```javascript
const crypto = require('crypto');

function verifyWebhookSignature(req, webhookSecret) {
    const rawBody = req.rawBody || JSON.stringify(req.body);
    const headerSig = req.headers['x-webhook-signature'] || '';
    
    const calculatedSig = crypto
        .createHmac('sha256', webhookSecret)
        .update(rawBody, 'utf8')
        .digest('hex');
    
    if (!crypto.timingSafeEqual(
        Buffer.from(calculatedSig),
        Buffer.from(headerSig)
    )) {
        throw new Error('Invalid signature');
    }
    
    return JSON.parse(rawBody);
}
```

### Retry Logic
If the webhook delivery fails:
- **First attempt:** Immediate
- **First retry:** After 5 seconds
- **Second retry:** After 25 seconds (exponential backoff)

**Note:** Webhook failures do NOT affect page creation. Pages are created successfully even if webhook delivery fails.

---

## Admin Interface

### API Keys Tab
- **Generate New API Key:** Create new keys
- **Keys List:** View all keys with status, usage stats, and actions
- **Revoke:** Instantly disable a key

### API Activity Log Tab
- **View Logs:** See all API requests (successful and failed)
- **Filters:**
  - Status (Success/Failed)
  - API Key
  - Date Range (From/To)
- **Export:** Download logs as CSV

### Created Pages Tab
- **View Pages:** List all pages created via API
- **Information:** Title, URL, creation date, and creator (API key name)
- **Quick Links:** Direct links to view/edit pages

### Settings Tab
- **Webhook Configuration:** Set webhook URL and secret
- **Rate Limiting:** Configure requests per hour
- **Global API Access:** Enable/disable API globally
- **Key Expiration Default:** Set default expiration period

### API Documentation Tab
- **Complete API Reference:** Endpoint, authentication, examples
- **cURL Examples:** Ready-to-use commands
- **Webhook Verification Guide:** How to verify signatures

---

## Security

### API Key Storage
- API keys are **never stored in plaintext**
- Keys are hashed using `password_hash()` with bcrypt
- Secrets are hashed separately
- Both key and secret are required for authentication

### Rate Limiting
- Per-key rate limiting (default: 100 requests/hour)
- Prevents abuse and DoS attacks
- Configurable in settings

### Request Logging
- All API requests are logged
- Includes: timestamp, API key, endpoint, status, IP address, response time
- Failed attempts are logged for security monitoring

### Webhook Security
- HMAC-SHA256 signature on every webhook
- Signature verification required on receiving end
- Prevents webhook spoofing

### Best Practices
1. **Never commit API keys to version control**
2. **Use environment variables for keys in production**
3. **Rotate keys regularly**
4. **Monitor activity logs for suspicious behavior**
5. **Use HTTPS for all API requests**
6. **Set appropriate expiration dates for keys**

---

## Troubleshooting

### Issue: "Invalid or expired API Key/Secret"
**Solutions:**
1. Verify you're using the correct API Key and Secret
2. Check that the key is Active (not revoked)
3. Ensure the key hasn't expired
4. Verify both headers are sent correctly (`X-API-Key` and `X-API-Secret`)
5. Check for extra spaces or hidden characters when copying keys

### Issue: "Rate limit exceeded"
**Solutions:**
1. Wait until the rate limit window resets (1 hour)
2. Generate a new API key if needed
3. Increase rate limit in Settings (if you have admin access)

### Issue: "API access disabled"
**Solutions:**
1. Go to **Settings** tab
2. Enable **Global API Access**
3. Save settings

### Issue: "Invalid JSON body passed"
**Solutions:**
1. Verify JSON syntax is correct (use a JSON validator)
2. Ensure Content-Type header is `application/json`
3. Check for special characters that need escaping
4. Use a JSON file with cURL instead of inline JSON

### Issue: Webhook not received
**Solutions:**
1. Verify webhook URL is correct in Settings
2. Check webhook secret is configured
3. Review API Activity Log for webhook delivery status
4. Ensure your webhook endpoint is accessible from the internet
5. Check firewall/security settings

### Issue: Pages not created
**Solutions:**
1. Check API response for error messages
2. Verify title and content are not empty
3. Review API Activity Log for details
4. Check WordPress permissions and user capabilities

---

## Support

For issues, questions, or contributions:
- Review the **API Documentation** tab in WordPress admin
- Check **API Activity Log** for request details
- Use **Test Auth** tab to verify API keys

---

## Version History

### Version 1.0.0
- Initial release
- REST API endpoint for bulk page creation
- API Key + Secret authentication
- Rate limiting
- Webhook notifications
- Admin interface
- Comprehensive logging

---

## License

This plugin is provided as-is for the technical assessment.

---

**End of Documentation**

