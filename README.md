# Unified Logging API Documentation

Unified Logging API adalah REST API untuk mencatat berbagai jenis event dan aktivitas aplikasi dengan integritas data menggunakan hash chain cryptographic.

## Table of Contents

- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [Rate Limiting](#rate-limiting)
- [Endpoints](#endpoints)
- [Log Types & Payload Requirements](#log-types--payload-requirements)
- [Response Formats](#response-formats)
- [Error Handling](#error-handling)
- [Examples](#examples)

---

## Quick Start

### 1. Get API Key

Daftarkan aplikasi Anda dan dapatkan API Key dari dashboard admin.

### 2. Verify API Key

```bash
curl -X GET https://api.example.com/v1/logs/verify \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### 3. Send Your First Log

```bash
curl -X POST https://api.example.com/v1/logs \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "log_type": "AUTH_LOGIN",
    "payload": {
      "user_id": 123,
      "email": "user@example.com",
      "ip": "192.168.1.1",
      "device": "Chrome on Windows"
    }
  }'
```

---

## Authentication

Semua request ke API harus menyertakan API Key di header:

```
Authorization: Bearer YOUR_API_KEY
```

### Verifying API Key

**Endpoint:** `GET /v1/logs/verify`

Gunakan endpoint ini untuk memverifikasi bahwa API Key Anda valid dan mendapatkan informasi aplikasi Anda.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "API Key is valid",
  "application": {
    "id": "app-uuid",
    "name": "My Application",
    "domain": "example.com",
    "stack": "Laravel"
  }
}
```

**Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid application context"
}
```

---

## Rate Limiting

API menerapkan rate limiting untuk menjaga stabilitas:

- **Limit:** 1000 request per menit per aplikasi
- **Header Response:** `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

Ketika limit terlampaui, API akan mengembalikan:

**Response (429 Too Many Requests):**
```json
{
  "success": false,
  "message": "Too Many Requests",
  "retry_after": 45
}
```

Tunggu `retry_after` detik sebelum melakukan request berikutnya.

---

## Endpoints

### POST /v1/logs

Mengirimkan log event ke sistem. Log akan divalidasi dan di-queue untuk diproses.

**URL:** `POST https://api.example.com/v1/logs`

**Headers:**
```
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

**Request Body:**
```json
{
  "log_type": "STRING (required)",
  "payload": {
    "field1": "value1",
    "field2": 123
  }
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Log received and queued for processing",
  "queued_at": "2024-01-15T10:30:45.123456Z"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "log_type": ["The log_type field is required."],
    "payload": ["The payload field is required."]
  }
}
```

**Response (422 Payload Validation Failed):**
```json
{
  "success": false,
  "message": "Payload validation failed",
  "errors": {
    "payload.user_id": ["The payload.user_id field is required."],
    "payload.email": ["The payload.email field must be a valid email."]
  }
}
```

---

## Log Types & Payload Requirements

Setiap log type memiliki struktur payload yang spesifik. Data akan divalidasi sebelum diterima.

### 1. Authentication Logs

#### AUTH_LOGIN
Log masuk pengguna ke aplikasi.

```json
{
  "log_type": "AUTH_LOGIN",
  "payload": {
    "user_id": 123,
    "email": "user@example.com",
    "ip": "192.168.1.1",
    "device": "Chrome on Windows"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID (nullable) |
| `email` | string | Email address |
| `ip` | string | IP address (nullable) |
| `device` | string | Device/browser info (nullable) |

---

#### AUTH_LOGOUT
Log keluar pengguna dari aplikasi.

```json
{
  "log_type": "AUTH_LOGOUT",
  "payload": {
    "user_id": 123,
    "email": "user@example.com",
    "ip": "192.168.1.1",
    "device": "Chrome on Windows"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID (nullable) |
| `email` | string | Email address |
| `ip` | string | IP address (nullable) |
| `device` | string | Device/browser info (nullable) |

---

#### AUTH_LOGIN_FAILED
Log percobaan login yang gagal.

```json
{
  "log_type": "AUTH_LOGIN_FAILED",
  "payload": {
    "user_id": null,
    "email": "user@example.com",
    "ip": "192.168.1.1",
    "device": "Safari on iOS"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID (nullable) |
| `email` | string | Email address |
| `ip` | string | IP address (nullable) |
| `device` | string | Device/browser info (nullable) |

---

### 2. Access & Download Logs

#### ACCESS_ENDPOINT
Log akses ke endpoint API atau resource tertentu.

```json
{
  "log_type": "ACCESS_ENDPOINT",
  "payload": {
    "user_id": 123,
    "endpoint": "/api/users/profile",
    "method": "GET",
    "ip": "192.168.1.1",
    "status": 200
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `endpoint` | string | API endpoint path |
| `method` | string | HTTP method (GET, POST, PUT, PATCH, DELETE) |
| `ip` | string | IP address (nullable) |
| `status` | integer | HTTP status code |

---

#### DOWNLOAD_DOCUMENT
Log download dokumen atau file.

```json
{
  "log_type": "DOWNLOAD_DOCUMENT",
  "payload": {
    "user_id": 123,
    "document_id": "doc-uuid",
    "document_name": "Report_2024.pdf",
    "ip": "192.168.1.1"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `document_id` | string/integer | Document ID |
| `document_name` | string | File name (nullable) |
| `ip` | string | IP address (nullable) |

---

#### SEND_EXTERNAL
Log pengiriman data ke channel eksternal (WhatsApp, Email, API).

```json
{
  "log_type": "SEND_EXTERNAL",
  "payload": {
    "user_id": 123,
    "channel": "WA",
    "to": "+62812345678",
    "message": "Your verification code is 123456",
    "meta": {
      "template_id": "verify-code",
      "message_id": "msg-uuid"
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `channel` | string | Channel (WA, EMAIL, API) |
| `to` | string | Recipient identifier |
| `message` | string | Message content (nullable) |
| `meta` | object | Additional metadata (nullable) |

---

### 3. Data Operation Logs

#### DATA_CREATE
Log pembuatan data/record baru.

```json
{
  "log_type": "DATA_CREATE",
  "payload": {
    "user_id": 123,
    "data": {
      "name": "John Doe",
      "email": "john@example.com",
      "role": "manager"
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `data` | object | Created data |

---

#### DATA_UPDATE
Log perubahan data/record.

```json
{
  "log_type": "DATA_UPDATE",
  "payload": {
    "user_id": 123,
    "before": {
      "name": "John Doe",
      "role": "user"
    },
    "after": {
      "name": "John Doe",
      "role": "manager"
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `before` | object | Data sebelum perubahan |
| `after` | object | Data setelah perubahan |

---

#### DATA_DELETE
Log penghapusan data/record.

```json
{
  "log_type": "DATA_DELETE",
  "payload": {
    "user_id": 123,
    "id": "record-uuid",
    "reason": "User requested account deletion"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `id` | string/integer | Record ID yang dihapus |
| `reason` | string | Alasan penghapusan (nullable) |

---

#### STATUS_CHANGE
Log perubahan status (order, task, dll).

```json
{
  "log_type": "STATUS_CHANGE",
  "payload": {
    "user_id": 123,
    "id": "order-uuid",
    "from": "pending",
    "to": "completed"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `id` | string/integer | Record ID |
| `from` | string | Status awal |
| `to` | string | Status akhir |

---

#### BULK_IMPORT
Log import data dalam jumlah besar.

```json
{
  "log_type": "BULK_IMPORT",
  "payload": {
    "user_id": 123,
    "total_rows": 1000,
    "success": 980,
    "failed": 20,
    "file_name": "users_import.csv"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `total_rows` | integer | Total baris yang diimport |
| `success` | integer | Baris berhasil |
| `failed` | integer | Baris gagal |
| `file_name` | string | Nama file (nullable) |

---

#### BULK_EXPORT
Log export data dalam jumlah besar.

```json
{
  "log_type": "BULK_EXPORT",
  "payload": {
    "user_id": 123,
    "total_rows": 5000,
    "success": 5000,
    "failed": 0,
    "file_name": "users_export.xlsx"
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID |
| `total_rows` | integer | Total baris yang diekport |
| `success` | integer | Baris berhasil |
| `failed` | integer | Baris gagal |
| `file_name` | string | Nama file (nullable) |

---

### 4. System Logs

#### SYSTEM_ERROR
Log error sistem/aplikasi.

```json
{
  "log_type": "SYSTEM_ERROR",
  "payload": {
    "message": "Database connection timeout",
    "code": "DB_TIMEOUT",
    "trace_id": "trace-uuid-12345",
    "context": {
      "database": "production",
      "query": "SELECT * FROM users WHERE id = ?"
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `message` | string | Error message |
| `code` | string | Error code (nullable) |
| `trace_id` | string | Unique trace identifier (nullable) |
| `context` | object | Additional context (nullable) |

---

#### VALIDATION_FAILED
Log validasi input yang gagal (biasanya di-trigger otomatis oleh sistem).

```json
{
  "log_type": "VALIDATION_FAILED",
  "payload": {
    "user_id": 123,
    "errors": {
      "email": ["Email harus valid"],
      "age": ["Age minimal 18"]
    },
    "ip": "192.168.1.1",
    "meta": {
      "original_log_type": "AUTH_LOGIN",
      "original_payload": { }
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID (nullable) |
| `errors` | object | Validation errors |
| `ip` | string | IP address (nullable) |
| `meta` | object | Additional metadata (nullable) |

---

### 5. Security Logs

#### SECURITY_VIOLATION
Log pelanggaran keamanan.

```json
{
  "log_type": "SECURITY_VIOLATION",
  "payload": {
    "user_id": 123,
    "ip": "192.168.1.1",
    "reason": "Multiple failed login attempts",
    "meta": {
      "attempts": 5,
      "time_window": "10 minutes"
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID (nullable) |
| `ip` | string | IP address (nullable) |
| `reason` | string | Deskripsi pelanggaran |
| `meta` | object | Additional details (nullable) |

---

#### PERMISSION_CHANGE
Log perubahan permission/role pengguna.

```json
{
  "log_type": "PERMISSION_CHANGE",
  "payload": {
    "user_id": 456,
    "target_user_id": 123,
    "before": {
      "role": "user",
      "permissions": ["read"]
    },
    "after": {
      "role": "admin",
      "permissions": ["read", "write", "delete"]
    }
  }
}
```

**Payload Schema:**
| Field | Type | Description |
|-------|------|-------------|
| `user_id` | integer | User ID yang melakukan perubahan |
| `target_user_id` | integer | User ID yang diubah |
| `before` | object | Permission sebelum perubahan |
| `after` | object | Permission setelah perubahan |

---

## Response Formats

### Success Response (202 Accepted)

```json
{
  "success": true,
  "message": "Log received and queued for processing",
  "queued_at": "2024-01-15T10:30:45.123456Z"
}
```

### Validation Error Response (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "log_type": [
      "The log_type field is required.",
      "The selected log_type is invalid."
    ],
    "payload.email": [
      "The payload.email field must be a valid email."
    ]
  }
}
```

---

## Error Handling

| Status | Message | Meaning |
|--------|---------|---------|
| 202 | Log received and queued | Request berhasil, log akan diproses |
| 401 | Invalid application context | API Key tidak valid atau expired |
| 422 | Validation failed | Format request atau payload salah |
| 429 | Too Many Requests | Rate limit terlampaui |
| 500 | Failed to process log request | Error di server saat memproses request |

### Recommended Retry Strategy

```javascript
// Pseudocode untuk retry logic
const maxRetries = 3;
let retryCount = 0;

async function sendLog(logData) {
  while (retryCount < maxRetries) {
    try {
      const response = await fetch('/api/v1/logs', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${API_KEY}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(logData)
      });

      if (response.status === 429) {
        const { retry_after } = await response.json();
        await sleep(retry_after * 1000);
        retryCount++;
        continue;
      }

      if (response.ok) {
        return await response.json();
      }

      throw new Error(`HTTP ${response.status}`);
    } catch (error) {
      retryCount++;
      if (retryCount >= maxRetries) throw error;
      await sleep(1000 * retryCount);
    }
  }
}
```

---

## Examples

### 1. JavaScript/Node.js

```javascript
const API_KEY = 'your-api-key';
const API_URL = 'https://api.example.com/v1';

async function sendLog(logType, payload) {
  const response = await fetch(`${API_URL}/logs`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      log_type: logType,
      payload: payload
    })
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(`Log failed: ${error.message}`);
  }

  return await response.json();
}

// Usage
await sendLog('AUTH_LOGIN', {
  user_id: 123,
  email: 'user@example.com',
  ip: '192.168.1.1',
  device: 'Chrome on Windows'
});
```

---

### 2. Python

```python
import requests
import json

API_KEY = 'your-api-key'
API_URL = 'https://api.example.com/v1'

def send_log(log_type, payload):
    headers = {
        'Authorization': f'Bearer {API_KEY}',
        'Content-Type': 'application/json'
    }
    
    data = {
        'log_type': log_type,
        'payload': payload
    }
    
    response = requests.post(
        f'{API_URL}/logs',
        headers=headers,
        json=data
    )
    
    if response.status_code != 202:
        raise Exception(f"Log failed: {response.json()['message']}")
    
    return response.json()

# Usage
send_log('AUTH_LOGIN', {
    'user_id': 123,
    'email': 'user@example.com',
    'ip': '192.168.1.1',
    'device': 'Chrome on Windows'
})
```

---

### 3. PHP/Laravel

```php
use Illuminate\Support\Facades\Http;

$apiKey = 'your-api-key';
$apiUrl = 'https://api.example.com/v1';

function sendLog($logType, $payload) {
    global $apiKey, $apiUrl;
    
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->post("{$apiUrl}/logs", [
        'log_type' => $logType,
        'payload' => $payload
    ]);
    
    if ($response->failed()) {
        throw new Exception("Log failed: {$response->json()['message']}");
    }
    
    return $response->json();
}

// Usage
sendLog('AUTH_LOGIN', [
    'user_id' => 123,
    'email' => 'user@example.com',
    'ip' => '192.168.1.1',
    'device' => 'Chrome on Windows'
]);
```

---

### 4. cURL

```bash
curl -X POST https://api.example.com/v1/logs \
  -H "Authorization: Bearer your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "log_type": "AUTH_LOGIN",
    "payload": {
      "user_id": 123,
      "email": "user@example.com",
      "ip": "192.168.1.1",
      "device": "Chrome on Windows"
    }
  }'
```

---

## Best Practices

1. **Always include user_id** ketika memungkinkan untuk audit trail yang lebih baik
2. **Use consistent log_type** untuk memudahkan analisis dan reporting
3. **Include IP address** untuk security tracking
4. **Handle rate limits gracefully** dengan retry exponential backoff
5. **Test dengan verify endpoint** terlebih dahulu sebelum mengirim logs
6. **Keep payload structure simple** dan hindari nested objects yang terlalu dalam
7. **Use meta field** untuk data tambahan yang tidak termasuk di payload utama

---

## Support

Untuk pertanyaan atau issue teknis, hubungi tim support melalui:
- Email: api-support@example.com
- Dashboard: https://dashboard.example.com/support
