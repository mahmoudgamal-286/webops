# MAHMOUD GAMAL

Production-ready WordPress plugin for bulk page creation via secure REST API with advanced authentication, rate limiting, and webhook notifications.

**Author:** MAHMOUD GAMAL

## 🚀 Features

- ✅ **REST API Endpoint:** `POST /wp-json/pagebuilder/v1/create-pages`
- ✅ **API Key + Secret Authentication:** Secure dual-header authentication
- ✅ **Rate Limiting:** Per-key rate limiting (default: 100 requests/hour)
- ✅ **Webhook Notifications:** HMAC-SHA256 signed webhooks with retry logic
- ✅ **Admin Interface:** Complete management UI under Tools → Page Builder
- ✅ **Activity Logging:** Comprehensive request logging with filters and CSV export
- ✅ **Security:** Hashed key storage, permissions system, IP tracking

## 📦 Installation

1. Copy the `simple-page-builder` folder to `wp-content/plugins/`
2. Activate **Simple Page Builder** in WordPress admin
3. Database tables are created automatically on activation

## ⚙️ Quick Start

### 1. Configure Settings
Navigate to **Tools → Page Builder → Settings**:
- Set Webhook URL and Secret (optional)
- Configure Rate Limit
- Enable Global API Access

### 2. Generate API Key
1. Go to **Tools → Page Builder → API Keys**
2. Click **Generate New API Key**
3. **⚠️ IMPORTANT:** Copy the Key and Secret immediately (shown only once)

### 3. Use the API

```bash
curl -X POST "https://your-site.com/wp-json/pagebuilder/v1/create-pages" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_64_CHAR_KEY" \
  -H "X-API-Secret: YOUR_32_CHAR_SECRET" \
  -d '[{"title":"My Page","content":"<p>Hello World</p>"}]'
```

## 📚 Documentation

For complete documentation, see **[DOCUMENTATION.md](DOCUMENTATION.md)** which includes:
- Detailed API reference
- Code examples (cURL, PHP, Python, JavaScript)
- Webhook verification guide
- Security best practices
- Troubleshooting guide

## 🔒 Security

- API keys stored as bcrypt hashes (never plaintext)
- Dual authentication (Key + Secret required)
- Rate limiting per key
- HMAC-SHA256 webhook signatures
- Request logging with IP tracking

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## 🛠️ Development

- **Main File:** `simple-page-builder.php`
- **Core Classes:** `includes/class-spb-*.php`
- **Admin Views:** `admin/views/*.php`

## 📄 License

Provided for technical assessment purposes.

---

**For complete documentation, examples, and troubleshooting, see [DOCUMENTATION.md](DOCUMENTATION.md)**

