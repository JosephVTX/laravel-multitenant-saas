# Laravel Multi-Tenant SaaS Boilerplate

Production-ready REST API boilerplate for multi-tenant SaaS applications, optimised for
[Laravel Octane](https://laravel.com/docs/octane) (FrankenPHP) and [Dokploy](https://dokploy.com).

- **Laravel 13** + **PHP 8.3/8.4**
- **tymon/jwt-auth** (`api` guard)
- **spatie/laravel-permission** with teams enabled (roles scoped per tenant)
- **spatie/laravel-query-builder** for filtering, sorting and includes
- Single database, shared schema, `tenant_id` isolation
- **One domain** (`saas.ndn.pe`) — no wildcard subdomains
- Versioned API: everything lives under `/api/v1`

---

## Multi-tenancy model

This boilerplate does **not** use subdomain or database-per-tenant tenancy. Every tenant
shares one database and every tenant-owned row carries a `tenant_id`. A single public
domain serves all tenants.

```
Request -> auth:api -> ResolveTenant -> EnsureTenant -> Controller
                            |                |
                     resolves tenant    blocks inactive
                     into TenantContext  tenants (403)
```

- `TenantContext` (`app/Support/Tenancy/TenantContext.php`) is a **scoped** container
  binding, so it is flushed between Octane requests.
- `TenantResolver` caches resolved tenants in Redis (`config/tenancy.php`) so the
  critical path stays free of repeated lookups.
- `BelongsToTenant` adds a global `TenantScope` and sets `tenant_id` on create.
- `TenantScope` only applies when a tenant is active in the context, so super-admin
  and central queries stay global.
- Spatie teams are keyed on `tenant_id`, so roles and permissions never leak across
  tenants. Super admins bypass authorization through a `Gate::before` check, not a role.

### Resolution rules

| Actor | How the tenant is resolved |
| --- | --- |
| Tenant user | Always from `users.tenant_id` (cannot be overridden) |
| Super admin | Optionally from the `X-Tenant` header (uuid, slug or id); otherwise central |

---

## Roles & permissions

Seeded with `RolePermissionSeeder`:

| Role | Permissions |
| --- | --- |
| `super-admin` | all (plus `Gate::before` bypass) |
| `tenant-admin` | `users.*`, `roles.manage`, `projects.*` |
| `tenant-manager` | `users.view`, `projects.view/create/update` |
| `tenant-member` | `projects.view/create` |

Permissions: `tenants.*`, `users.*`, `roles.manage`, `projects.*`.

---

## API surface (v1)

All responses use a consistent envelope:

```json
{ "success": true, "message": "OK", "data": { }, "meta": { }, "links": { } }
```

Errors return `{ "success": false, "message": "…", "code": "…", "errors": { } }`.

### Public / auth

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/health` | Health probe |
| `POST` | `/api/v1/auth/login` | JWT login (rate limited) |
| `POST` | `/api/v1/auth/refresh` | Refresh a token |
| `POST` | `/api/v1/auth/logout` | Invalidate the token |
| `GET` | `/api/v1/auth/me` | Current user (+ tenant, roles, permissions) |

### Super-admin panel (`super.admin`)

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/admin/stats` | Platform statistics |
| `GET/POST` | `/api/v1/admin/tenants` | List / create tenants |
| `GET/PUT/DELETE` | `/api/v1/admin/tenants/{tenant}` | Show / update / delete |
| `POST` | `/api/v1/admin/tenants/{tenant}/suspend` | Suspend |
| `POST` | `/api/v1/admin/tenants/{tenant}/activate` | Activate |
| `GET/POST` | `/api/v1/admin/tenants/{tenant}/users` | Manage a tenant's users |
| `GET/POST` | `/api/v1/admin/users` | All users / create |
| `GET/PUT/DELETE` | `/api/v1/admin/users/{user}` | Show / update / delete |
| `POST` | `/api/v1/admin/users/{user}/roles` | Assign roles |
| `GET/POST` | `/api/v1/admin/roles` | List / create roles |
| `PUT/DELETE` | `/api/v1/admin/roles/{role}` | Update / delete |
| `GET/POST` | `/api/v1/admin/permissions` | List / create permissions |

Creating a tenant can create its first owner in one call:

```json
POST /api/v1/admin/tenants
{
  "name": "Acme Corp",
  "plan": "pro",
  "owner": { "name": "Owner", "email": "owner@acme.test", "password": "secret123" }
}
```

### Tenant workspace (`auth:api` + `tenant`)

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/v1/tenant/profile` | Current tenant |
| `GET` | `/api/v1/tenant/roles` | Assignable roles |
| `GET/POST` | `/api/v1/tenant/users` | Tenant users |
| `PUT/DELETE` | `/api/v1/tenant/users/{user}` | Update / delete |
| `POST` | `/api/v1/tenant/users/{user}/roles` | Assign roles |
| `GET/POST` | `/api/v1/tenant/projects` | Example tenant resource |
| `GET/PUT/DELETE` | `/api/v1/tenant/projects/{project}` | Show / update / delete |

### Query builder

List endpoints accept `filter[...]`, `sort`, `include` and `per_page` (clamped to
`TENANT_PAGINATION_MAX`):

```
GET /api/v1/admin/tenants?filter[status]=active&sort=-created_at&include=users&per_page=25
GET /api/v1/tenant/projects?filter[status]=active&sort=name
```

---

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# configure DB_* and REDIS_* in .env, then:
php artisan migrate --seed
php artisan serve
```

`migrate --seed` creates the super admin from `SUPER_ADMIN_*` (default
`admin@saas.ndn.pe` / `password`) and, when `SEED_DEMO_TENANT=true`, a demo tenant
(`demo`, `admin@demo.test` / `password`).

## Testing

```bash
php artisan test --compact
```

The suite covers authentication, super-admin provisioning and cross-tenant isolation
(tenant-scoped reads return `404`, never `403`, to avoid confirming existence).

---

## Deploying to Dokploy

The repo ships a multi-stage `Dockerfile` (FrankenPHP + Octane, PHP 8.4) and
`docker/entrypoint.sh`.

1. **Create the data services** in Dokploy: a MySQL database and a Redis database.
2. **Create an Application** from this Git repository.
   - Build type: **Dockerfile**, path `Dockerfile`.
   - Port: `8000`.
   - Health check path: `/api/v1/health`.
3. **Environment variables** (see `.env.example`). Minimum for a first boot:

   ```
   APP_NAME="SaaS API"
   APP_ENV=production
   APP_KEY=            # php artisan key:generate --show
   APP_DEBUG=false
   APP_URL=https://saas.ndn.pe

   DB_CONNECTION=mysql
   DB_HOST=<mysql host>
   DB_PORT=3306
   DB_DATABASE=<db>
   DB_USERNAME=<user>
   DB_PASSWORD=<password>

   REDIS_HOST=<redis host>
   REDIS_PORT=6379
   REDIS_PASSWORD=<password or null>
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis

   JWT_SECRET=          # php artisan jwt:secret --show
   TENANT_CENTRAL_DOMAIN=saas.ndn.pe

   SUPER_ADMIN_EMAIL=admin@saas.ndn.pe
   SUPER_ADMIN_PASSWORD=<strong password>

   RUN_MIGRATIONS=true
   ```

   The entrypoint caches config/routes and runs `migrate --force` when
   `RUN_MIGRATIONS=true` (set it back to `false` after the first deploy).
4. **Domain**: point `saas.ndn.pe` (`A` record) to the Dokploy server and add it to the
   application; enable Let's Encrypt.

### Local container test

```bash
docker build -t laravel-multitenant-saas .
docker run --rm -p 8000:8000 --env-file .env laravel-multitenant-saas
```

---

## Adding a new tenant-scoped module

1. Create the migration with a `tenant_id` foreign key and a composite index whose
   **first column is `tenant_id`** (e.g. `index(['tenant_id', 'status'])`).
2. Add `use BelongsToTenant;` to the model — scoping and assignment become automatic.
3. Add `use HasUuid;` when the resource should be addressed publicly by UUID.
4. Add a policy keyed on the module's permissions and reuse the existing middlewares.
5. Add feature tests proving cross-tenant reads return `404`.
