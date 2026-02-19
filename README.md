# Community Waste Collection System

[![GitHub Repository](https://img.shields.io/badge/GitHub-community--waste-blue?logo=github)](https://github.com/fawwasaldy/community-waste)
[![Laravel](https://img.shields.io/badge/Laravel-v12-red?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.5-purple?logo=php)](https://php.net)
[![MongoDB](https://img.shields.io/badge/MongoDB-8-green?logo=mongodb)](https://mongodb.com)

## Introduction

Community Waste Collection System is a REST API backend for managing residential waste pickup and payment tracking. It serves two user roles:

- **Household Owner (Guest)** — can register a household, submit pickup requests, and view reports without authentication.
- **Officer (Authenticated Admin)** — logs in via JWT to schedule/complete/cancel pickups and manage payments.

![waste-community](https://github.com/user-attachments/assets/844ada7d-44b8-478b-a737-8d4506bedf3a)

## API Documentation

Full interactive documentation is available on Postman:

**[View Postman Docs →](https://documenter.getpostman.com/view/47165783/2sBXcDHgr4)**

You can access the public API at `https://waste.cyanstar.dev/api`

## Requirements

- [Docker](https://docs.docker.com/get-docker/) 24+
- [Docker Compose](https://docs.docker.com/compose/install/) v2+

## (Option 1) Deploy with Docker Compose

This method builds the image locally from source.

```bash
# 1. Clone the repository
git clone https://github.com/fawwasaldy/community-waste.git
cd community-waste

# 2. Configure environment
cp .env.example .env
# Edit .env — set JWT_SECRET, MONGO_ROOT_USERNAME, MONGO_ROOT_PASSWORD, MONGODB_DATABASE

# 3. Build images, install dependencies, generate APP_KEY, and run migrations
make setup

# 4. Verify the setup (optional)
make test
```

The API will be available at `http://localhost:8080`.

## (Option 2) Deploy with Docker Image

This method pulls the pre-built image from GitHub Container Registry — no source code required.

```bash
# Pull the latest image
docker pull ghcr.io/fawwasaldy/community-waste:latest
```

Create a `docker-compose.prod.yml` (use the one in the repo) with the following environment variables:

```yaml
services:
  app:
    image: ghcr.io/fawwasaldy/community-waste:latest
    ports:
      - "127.0.0.1:8080:80"
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_KEY: ${APP_KEY}
      APP_URL: ${APP_URL}
      JWT_SECRET: ${JWT_SECRET}
      DB_CONNECTION: mongodb
      MONGODB_URI: "mongodb://${MONGO_ROOT_USERNAME}:${MONGO_ROOT_PASSWORD}@mongodb:27017/?replicaSet=rs0&authSource=admin"
      MONGODB_DATABASE: ${MONGODB_DATABASE}
    depends_on:
      mongo-init:
        condition: service_completed_successfully
    restart: unless-stopped

  # ... mongodb, mongo-keygen, mongo-init services (see docker-compose.prod.yml)
```

Create a `.env` with the following environment variables:

```env
IMAGE_NAME=ghcr.io/fawwasaldy/community-waste
APP_KEY=<generated from make setup or php artisan key:generate>
JWT_SECRET=<generated from make setup or php artisan jwt:secret>
APP_URL=<your app URL, e.g., http://localhost:8080>
APP_ENV=production
APP_DEBUG=false
MONGODB_DATABASE=community-waste
MONGO_ROOT_USERNAME=<your mongo root username, e.g., admin>
MONGO_ROOT_PASSWORD=<your mongo root password, e.g., secret123>

```

Then start:

```bash
docker compose -f docker-compose.prod.yml up -d
```

## Migrations & Seeding

After the containers are running, exec into the `app` container using one of the following options:

**Option 1 — by container name:**
```bash
docker exec -it community-waste-app-1 bash
```

**Option 2 — via Docker Compose:**
```bash
docker compose -f docker-compose.prod.yml exec app bash
```

Then run migrations and seed static data (officer account + initial config):

```bash
php artisan migrate
php artisan db:seed --class=StaticSeeder
```

## API Overview

All routes are prefixed with `/api`. Routes marked **Auth required** need a `Authorization: Bearer <token>` header.

| Group | Prefix | Auth Required | Description |
|-------|--------|:---:|-------------|
| `auth` | `/api/register`, `/api/login`, `/api/me`, `/api/logout`, `/api/refresh` | Partial | JWT authentication via `php-open-source-saver/jwt-auth`. Register and login are public; me, logout, and refresh require a valid token. |
| `household` | `/api/households` | No | Full CRUD for household records (`owner_name`, `address`, `block`, `no`). Public — no authentication needed. |
| `pickup` | `/api/pickups` | Partial | Waste pickup lifecycle. Creating a pickup and listing pickups are public. Scheduling, completing, and canceling require officer auth. Statuses flow: `pending` → `scheduled` → `completed` / `canceled`. |
| `payment` | `/api/payments` | Yes | Officer-only payment management. Confirming a payment sets `payment_date` and transitions status from `pending` to `paid` or `failed`. |
| `report` | `/api/reports/*` | No | Read-only summary endpoints: waste summary, payment summary, and per-household history. No authentication required. |

## Example API Requests

### Register an Officer Account

```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Officer Ahmad", "email": "ahmad@example.com", "password": "secret123", "password_confirmation": "secret123"}'
```

### Login

```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "ahmad@example.com", "password": "secret123"}'
```

Response includes `access_token` — use it as a Bearer token for authenticated routes.

### Create a Household

```bash
curl -X POST http://localhost:8080/api/households \
  -H "Content-Type: application/json" \
  -d '{"owner_name": "Budi Santoso", "address": "Jl. Merdeka No. 5", "block": "A", "no": "05"}'
```

### Submit a Pickup Request (public)

```bash
curl -X POST http://localhost:8080/api/pickups \
  -H "Content-Type: application/json" \
  -d '{"household_id": "<household_id>", "type": "organic", "pickup_date": "2026-02-25"}'
```

Supported waste types: `organic`, `plastic`, `paper`, `electronic`.

### Schedule a Pickup (officer only)

```bash
curl -X PUT http://localhost:8080/api/pickups/<pickup_id>/schedule \
  -H "Authorization: Bearer <access_token>"
```

### Confirm a Payment (officer only)

```bash
curl -X PUT http://localhost:8080/api/payments/<payment_id>/confirm \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{"status": "paid", "payment_date": "2026-02-19"}'
```

### View Waste Summary Report

```bash
curl http://localhost:8080/api/reports/waste-summary
```

## Technical Decisions

### 1. JWT Authentication (php-open-source-saver/jwt-auth)

The system uses stateless JWT tokens rather than Laravel Sanctum's default session/cookie approach. This fits a pure REST API that may be consumed by mobile clients or third-party integrations without shared session state. The `php-open-source-saver/jwt-auth` package is the maintained community fork compatible with Laravel 12 and PHP 8.x.

### 2. Role Model: Guest vs. Officer (No RBAC Package)

Rather than installing a full RBAC package, the system uses a simple two-tier model: unauthenticated (guest/household owner) and authenticated (officer). The `auth:api` middleware guards officer-only routes. This keeps the codebase lean while meeting the current access control requirements.

### 3. Docker Compose with Replica Set for Local Development

MongoDB transactions (required for atomic status updates) need a replica set — even locally. The `docker-compose.yml` spins up a single-node replica set (`rs0`) automatically via a `mongo-init` service, removing the manual setup burden. Production (`docker-compose.prod.yml`) adds keyfile authentication for inter-node security.

### 4. Waste Model Polymorphism — Single Collection with Type Discriminator

Two approaches were considered for storing different waste types:

- **Separate collections per type** — `waste_organics`, `waste_electronics`, etc., one collection per waste category.
- **Single `wastes` collection** — all types in one collection, distinguished by a `type` discriminator field.

The single-collection approach was chosen. Because MongoDB is schemaless, each document can carry its own shape depending on its `type` — for example, only `WasteElectronic` documents include a `safety_check` field; the other types simply omit it.

**Implementation:** Four child models (`WasteOrganic`, `WastePlastic`, `WastePaper`, `WasteElectronic`) each extend the base `Waste` model. Each child applies a global scope in `booted()` to automatically filter queries by its type, and a `creating()` hook to auto-assign the `type` field on save. A `WasteRepository` maps type strings to their corresponding model classes for polymorphic instantiation.

**Benefit:** All queries run against one collection, type-specific behavior (validation rules, payment amounts, extra fields) is encapsulated in each child class, and Laravel handles dispatch cleanly without raw conditionals scattered through the codebase.
