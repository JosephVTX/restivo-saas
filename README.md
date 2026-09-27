# Restivo — POS y gestión para restaurantes

Restivo es un sistema **multi-tenant** (un solo dominio, una sola base de datos)
para restaurantes en Perú: los mozos toman pedidos desde el celular o tablet, la
cocina ve las comandas en pantalla, la caja cobra y cuadra el turno, y cada
cliente puede emitir boleta/factura electrónica ante SUNAT. Incluye KPIs de
ventas y operación.

Backend Laravel 13 + SPA Inertia/React 19 en un monólito, optimizado para
servidores de bajos recursos (Octane/FrankenPHP + Redis).

## Módulos

- **Salón:** zonas y mesas con estado (libre, ocupada, por cobrar, reservada).
- **Carta:** categorías, platos (precio con IGV, tipo SUNAT, estación,
  disponibilidad) y modificadores (término, extras).
- **POS del mozo:** toma rápida por mesa con botones grandes; envío a cocina.
- **Cocina (KDS):** tablero Pendiente / En preparación / Listo, avanzable con un
  toque (sin impresión).
- **Caja:** apertura/cierre de turno con arqueo, pagos divididos, propina,
  vuelto y movimientos de ingreso/egreso.
- **Comprobantes:** nota de venta (ticket PDF), boleta y factura electrónica,
  anulación.
- **Facturación electrónica:** Greenter + SUNAT configurable por cliente
  (RUC, usuario SOL, certificado digital, series, modo beta/producción).
  Opcional: apagada por defecto.
- **Clientes:** DNI/RUC reutilizables para el comprobante.
- **KPIs:** ventas, pedidos, ticket promedio, propinas, ventas por método y por
  hora, top platos/categorías/mozos, ocupación de mesas y comparación de periodo.
- **Roles:** Propietario, Administrador, Cajero, Mozo, Cocinero y Miembro.

## Stack

- **Backend:** PHP 8.3, Laravel 13, Octane, spatie/laravel-permission (teams),
  spatie/laravel-query-builder, Redis, greenter/lite, dompdf.
- **Frontend:** Inertia, React 19, TypeScript, Vite 8, Tailwind CSS v4,
  daisyUI v5 (temas propios `restivo` / `restivo-dark`), SWR, axios, zod, oxlint.
- **Idioma:** interfaz y validaciones en **español** (`lang/es`, locale `es`),
  con defaults regionales de **Lima, Perú** (zona horaria `America/Lima`, moneda
  `PEN`). Los identificadores de roles/permisos se mantienen en inglés; solo sus
  etiquetas se traducen.
- **Tenancy:** single database, aislamiento por `tenant_id` + global scope,
  resolver cacheado, roles/permisos por tenant.

## Requisitos

PHP 8.3+, Composer, Node 20+ con **pnpm**, MySQL 8+, Redis. Para probar el envío
real a SUNAT se necesita la extensión `soap` (ya incluida en el `Dockerfile`).

## Puesta en marcha local (Laragon)

```sh
composer install
pnpm install
cp .env.example .env
php artisan key:generate

# crea la base de datos (Laragon -> MySQL)
php artisan migrate:fresh --seed
pnpm build   # o: pnpm dev
```

Apunta tu host local a `public/` (p. ej. `http://restivo.test`).

### Accesos demo

| Rol | Email | Password |
| --- | --- | --- |
| Super admin | `superadmin@example.com` | `password` |
| Propietario | `owner@example.com` | `password` |
| Mozo | `waiter@example.com` | `password` |
| Cajero | `cashier@example.com` | `password` |
| Cocinero | `kitchen@example.com` | `password` |

La raíz `/` abre el login; al entrar te lleva a tu panel.

## Scripts

```sh
php artisan test                         # PHPUnit (sqlite in-memory)
vendor/bin/pint --dirty --format agent   # formato PHP
pnpm lint                                # oxlint
pnpm dev / pnpm build                    # Vite dev / build de producción
php artisan migrate:fresh --seed         # reinicia datos demo
```

## Alta de clientes (registro cerrado)

No hay registro público. El super admin crea el restaurante y da acceso:

1. Entra como super admin (`superadmin@example.com`).
2. En **Admin → Tenants**, crea el tenant y su propietario. Si dejas la
   contraseña vacía, se genera una temporal y se muestra una sola vez.
3. Usa la acción de otorgar acceso para sumar más miembros (mozo, cajero, etc.).

Internamente: `POST /api/v1/admin/tenants` y
`/api/v1/admin/tenants/{tenant}/members` vía `App\Services\Tenancy\AccessGranter`.

## Facturación electrónica

Cada restaurante la activa en **Facturación** (`/app/settings/billing`):
RUC, razón social, usuario SOL secundario, certificado digital (.p12/.pem),
series y modo (beta o producción). Mientras esté apagada, solo se emiten notas
de venta internas (PDF). El envío real a SUNAT requiere `ext-soap` y un
certificado válido.

## Tenancy

Los datos se aíslan por `tenant_id` con un global scope. El tenant activo sale de
la sesión (`tenant_id`), con fallback a la primera membresía del usuario; el
super admin puede impersonar cualquier tenant. Ver `.ai/rules/tenancy.md` y
`docs/ARCHITECTURE.md`.

## Despliegue (Docker + FrankenPHP + Octane)

`compose.yaml` levanta la app con FrankenPHP/Octane, MySQL y Redis con memoria
acotada, ideal para VPS pequeños.

```sh
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force   # datos demo (opcional)
```

## Agregar un recurso

Sigue la receta de 6 pasos de `.ai/rules/http-layer.md`: mantiene la separación
entre controladores Inertia y controladores JSON `/api/v1`, y el patrón de
consulta con scope de tenant.
