# REST API Documentation (v1)

The DNS Manager exposes a RESTful API authenticated via **Laravel Sanctum Bearer Tokens**.

Base URL: `https://your-domain.com/api/v1`

---

## 1. Authentication

### `POST /api/v1/login`
Authenticate and retrieve a personal access token.

**Request:**
```json
{
  "email": "admin@example.com",
  "password": "secretpassword",
  "token_name": "production-api"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Authenticated successfully.",
  "data": {
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Super Admin",
      "email": "admin@example.com",
      "role": "super_admin",
      "customer_id": null
    },
    "abilities": ["*"]
  }
}
```

---

## 2. DNS Zones Endpoints

All authenticated requests require the header:  
`Authorization: Bearer <token>`  
`Accept: application/json`

### `GET /api/v1/zones`
List all zones (automatically scoped to customer account if authenticated as Customer).

**Parameters:**
- `search` (optional): Filter by domain name substring
- `type` (optional): `NATIVE`, `MASTER`, `SLAVE`
- `status` (optional): `active`, `disabled`, `suspended`
- `customer_id` (optional)
- `per_page` (optional): Default 15

### `POST /api/v1/zones`
Create a new authoritative DNS Zone.

**Request:**
```json
{
  "name": "pelangicomm.net",
  "type": "NATIVE",
  "customer_id": 1,
  "dns_server_id": 1,
  "status": "active"
}
```

### `GET /api/v1/zones/{id}`
Get full details of a specific zone with records count.

### `PUT /api/v1/zones/{id}`
Update zone properties (e.g. status, customer_id, dns_server_id).

### `DELETE /api/v1/zones/{id}`
Delete zone and all its records.

### `POST /api/v1/zones/{id}/sync`
Synchronize zone consistency and trigger PowerDNS notification.

---

## 3. DNS Records Endpoints

### `GET /api/v1/zones/{id}/records`
List all resource records in a zone.

### `POST /api/v1/zones/{id}/records`
Create a new DNS record.

**Request:**
```json
{
  "name": "www",
  "type": "A",
  "content": "103.10.10.1",
  "ttl": 3600
}
```

### `GET /api/v1/records/{id}`
Get record details.

### `PUT /api/v1/records/{id}`
Update record value or TTL. Automatically updates the zone SOA serial!

### `DELETE /api/v1/records/{id}`
Delete record. Automatically updates the zone SOA serial! (SOA record cannot be deleted).

---

## 4. DNS Servers & Customers

- `GET /api/v1/dns-servers` — List nameserver nodes
- `GET /api/v1/dns-servers/{id}` — Nameserver details
- `POST /api/v1/dns-servers/{id}/health-check` — Trigger immediate health check
- `GET /api/v1/customers` — List ISP customers
- `GET /api/v1/customers/{id}` — Customer details & zones list
