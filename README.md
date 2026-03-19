# Desafío Asset — URL Shortener API

Servicio acortador de URLs construido con **Laravel 12** y **PHP 8.2+**. Permite crear slugs personalizados o aleatorios, redirigir visitas, registrar estadísticas de clics (país, referer, clicks por día) mediante jobs en cola, y proteger recursos con API key.

---

## Requisitos previos

| Herramienta | Versión mínima |
|-------------|---------------|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| MySQL | 8.0 (o MariaDB 10.6+) |

---

## Instalación y configuración

### 1. Clonar el repositorio

```bash
git clone https://github.com/carlosfdezb/desafio-asset.git
cd desafio-asset
```

### 2. Instalación rápida (un solo comando)

```bash
composer setup
```

Esto ejecuta automáticamente:

1. `composer install` — instala dependencias PHP.
2. Copia `.env.example` a `.env` si no existe.
3. `php artisan key:generate` — genera la clave de la aplicación.
4. `php artisan migrate --force` — ejecuta las migraciones.
5. `npm install` — instala dependencias JS.
6. `npm run build` — compila assets con Vite.

### 3. Configurar variables de entorno

Edita el archivo `.env` con los datos de tu base de datos:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=desafio_asset
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

### 4. Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

### 5. Levantar el entorno de desarrollo

```bash
composer dev
```

Esto inicia en paralelo:

| Proceso | Descripción |
|---------|-------------|
| `php artisan serve` | Servidor web en `http://localhost:8000` |
| `php artisan queue:listen` | Worker de colas (procesa jobs de estadísticas) |
| `php artisan pail` | Visor de logs en tiempo real |
| `npm run dev` | Servidor Vite para hot-reload de assets |

> También puedes iniciar cada proceso por separado si lo prefieres.

---

## Arquitectura del proyecto

```
app/
├── Http/
│   ├── Controllers/       # SlugController, StatController, HealthController
│   └── Requests/          # Form Requests con validación
├── Jobs/
│   └── RecordStatJob.php  # Job en cola para registrar estadísticas (GeoIP)
├── Models/                # Slug, Stat
├── Repositories/          # Repositorios con contratos (interfaces)
└── Services/              # Lógica de negocio (SlugService, StatService)
```

- **Colas**: las estadísticas de cada clic se registran de forma asíncrona mediante `RecordStatJob` (driver `database`).
- **GeoIP**: se usa el paquete `torann/geoip` con el servicio IP-API para obtener el país del visitante.
- **Soft Deletes**: los slugs eliminados se marcan como borrados sin eliminarse físicamente de la BD.

---

## API Reference

**Base URL**: `http://localhost:8000`

Todas las rutas de la API usan el prefijo `/api` salvo la redirección (`GET /{slug}`) que es una ruta web.

---

### 1. Acortar URL

Crea un nuevo slug (aleatorio o personalizado) para una URL.

```
POST /api/shorten
Content-Type: application/json
```

**Request body**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `url` | string | ✅ | URL original a acortar. Debe ser una URL válida (`http` o `https`). |
| `custom_slug` | string | ❌ | Slug personalizado (máx. 100 caracteres). Si no se envía, se genera uno aleatorio de 8 caracteres. |
| `api_key` | string | ❌ | Clave para proteger el slug (mín. 8, máx. 255 caracteres). Necesaria luego para eliminar el slug o ver stats privadas. |

**Ejemplo de request**:

```json
{
  "url": "https://example.com/articulo-largo",
  "custom_slug": "mi-link",
  "api_key": "mi-clave-secreta"
}
```

**Respuesta exitosa** (`200 OK`):

```json
{
  "short_url": "http://localhost:8000/mi-link",
  "slug": "mi-link",
  "original_url": "https://example.com/articulo-largo"
}
```

**Errores posibles**:

| Código | Descripción |
|--------|-------------|
| `422` | Validación fallida (URL faltante, formato inválido, etc.) |
| `404` | No fue posible generar un slug único. |

---

### 2. Eliminar slug

Elimina un slug existente (soft delete). Solo se puede eliminar si fue creado con `api_key`.

```
DELETE /api/{slug}
Content-Type: application/json
```

**Request body**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `api_key` | string | Condicional | Obligatoria si el slug fue creado con API key. Mín. 8, máx. 255 caracteres. |

**Ejemplo de request**:

```json
{
  "api_key": "mi-clave-secreta"
}
```

**Respuesta exitosa** (`200 OK`):

```json
{
  "message": "Slug eliminado correctamente."
}
```

**Errores posibles**:

| Código | Descripción |
|--------|-------------|
| `400` | API key requerida, inválida, o el slug no puede ser eliminado (creado sin API key). |
| `404` | Slug no encontrado. |
| `422` | Validación fallida en el formato de la API key. |

---

### 3. Obtener estadísticas de un slug

Devuelve las estadísticas de clics de un slug: total de clicks, clicks por día, top referers y clicks por país.

```
GET /api/stats/{slug}
```

**Headers**:

| Header | Tipo | Requerido | Descripción |
|--------|------|-----------|-------------|
| `X-API-Key` | string | Condicional | Obligatorio si el slug fue creado con API key. |

**Ejemplo de request**:

```bash
curl -H "X-API-Key: mi-clave-secreta" http://localhost:8000/api/stats/mi-link
```

**Respuesta exitosa** (`200 OK`):

```json
{
  "slug": "mi-link",
  "original_url": "https://example.com/articulo-largo",
  "total_clicks": 42,
  "clicks_per_day": [
    { "date": "2026-03-19", "clicks": 15 },
    { "date": "2026-03-18", "clicks": 27 }
  ],
  "top_referers": [
    { "referer": "google.com", "clicks": 20 },
    { "referer": "twitter.com", "clicks": 10 }
  ],
  "clicks_by_country": [
    { "country": "Chile", "clicks": 25 },
    { "country": "Argentina", "clicks": 17 }
  ]
}
```

**Errores posibles**:

| Código | Descripción |
|--------|-------------|
| `403` | API key inválida o no proporcionada (slug protegido). |
| `404` | Slug no encontrado. |

---

### 4. Redireccionar por slug

Redirige al usuario a la URL original asociada al slug. Además, despacha un job en cola para registrar la estadística del clic (referer, país vía GeoIP, timestamp).

```
GET /{slug}
```

> **Nota**: esta ruta es web (sin prefijo `/api`).

**Respuesta exitosa**: redirección HTTP `302` a la URL original.

**Errores posibles**:

| Código | Descripción |
|--------|-------------|
| `404` | Slug no encontrado. |

---

### 5. Health Check

Verifica el estado de la aplicación y la conexión a la base de datos.

```
GET /api/health
```

**Respuesta exitosa** (`200 OK`):

```json
{
  "status": "ok",
  "database": "connected",
  "timestamp": "2026-03-19T15:00:00.000000Z"
}
```

**Respuesta con error de BD**:

```json
{
  "status": "error",
  "database": "disconnected",
  "timestamp": "2026-03-19T15:00:00.000000Z"
}
```

---

## Resumen de endpoints

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `POST` | `/api/shorten` | Acortar URL | — |
| `DELETE` | `/api/{slug}` | Eliminar slug | API key (body) |
| `GET` | `/api/stats/{slug}` | Estadísticas del slug | API key (header `X-API-Key`) |
| `GET` | `/{slug}` | Redireccionar a URL original | — |
| `GET` | `/api/health` | Health check | — |


# Explicaciones técnicas

### A. Generación de slugs únicos
Para garantizar slugs únicos lo separé en 2 casos:

 1. Para peticiones sin custom_slug, acá utilizo la generación de 8 caracteres aleatorios. Si bien es dificil que se creen 2 veces el mismo valor, considerando la posible escabilidad (millones de registros) implementé reintentos (hasta 5) donde por cada ciclo se crea un slug y se valida que no exista.
 2. Para peticiones con custom_slug, acá la lógica cambia, ya que se pueden crear N registros con el 'mismo' custom_slug. Primero se valida si el slug base está disponible. Si ya existe, se generan variantes incrementales siguiendo el patrón custom_slug, custom_slug-1, custom_slug-2, ..., custom_slug-N. También en estos casos tenemos otro problema, si llegan 2 peticiones al mismo tiempo con el mismo custom_slug, para este caso preferí usar Cache::lock, por el prefijo (custom_slug base), para que solo una solicitud pueda obtener el próximo correlativo, haciendo esperar a la otra.

 ### B. Performance en la redirección

 Para que el redirect sea lo más rapido posible para no afectar la experiencia del usuario, dejé la request de redirección enfocada únicamente en resolver el slug y devolver la Url original. El registro de estadísticas no se procesa de forma síncrona en esa misma request, sino que se delega a un job en cola. De esta forma todo el almacenamiento de analytics queda desacoplado del flujo crítico del usuario.

### C. Esquema de base de datos para analytics

Con la separación que existe entre slugs y stats, donde todos los eventos se registran en la segunda evitamos sobrecargar la tabla principal con los datos analíticos. Considerando esto podríamos implementar índices orientados a las métricas requeridas, como por ejemplo; índice compuesto por slug_id y clicked_at para agrupar por día, índice compuesto por slug_id y country para clicks por país, etc.

### D. Autorización con API key

Para proteger la Api Key indicada por el usuario al momento de la creación, no la guardo como texto plano, sino que aplico un hash, con el fin de no exponer la credencial original incluso si alguien accede a la tabla. Para validar si el dueño es quien quiere ver las stats o eliminar, primero valido si el slug tiene un Api key asociado, y posteriormente lo comparo con el entregado, usando Hash::check.

### E. Concurrencia al crear links

Como lo mencioné en el punto A.2., me decanté por el uso de Cache::lock, para evitar la concurrencia. De todas formas por BD, el campo indicado es unique, por lo que siempre habrá una última capa de defensa.

### F. Contrato de errores

Para mantener consistencia en las respuesta de error, utilicé un formato Json simple y predecible, con la siguiente estructura:
```
{
  "message": "Descripción legible del error."
}
```

Y para el particular de los casos: 422 para url inválida, 404 para link inexistente, 403 para Api key incorrecta, 500 cuando no se pudo generar un slug único.

### G. Estrategia de cache

Para este caso consideraría cachear las métricas, como total_clicks, clicks_per_day, clicks_by_country, etc. Pero considerando lo fluctuante de estas, guardaría las respuestas de estas estadísticas con una expiración breve (de 1 a 5 minutos). Con esto aliviamos la carga en la bd y los datos siguen siendo lo suficientemente frescos. Otra opción sería cachear indeterminadamente hasta que en dicho slug se registre una nueva estádistica, haciendo que el mismo job se encargue de invalidar el caché.

# Mejoras a futuro

La principal estaría en el módulo de estadísticas. Actualmente la solución funciona con una sola tabla de eventos y consultas agregadas sobre ella, pero si el volumen de visitas creciera significativamente, preferiría tomar un enfoque hacia un esquema con tablas resumidas, por ejemplo, incorporaría tablas para:

 - Clicks por día (tipo slug_daily_stats)
 - Clicks por país (tipo slug_country_stats)
 - etc.

Con esto reduciriamos mucho el costo de los count y group by.

Otra mejora que evaluaría sería la generación concurrente de slugs personalizados. Actualmente resolví ese problema con Cache::lock(), lo que funciona bien y mantiene la solución simple. Sin embargo, si el sistema creciera más, consideraría reemplazar este enfoque por una estrategia basada en secuencias o contadores por slug base. Por ejemplo, podría existir una tabla dedicada que mantenga el último correlativo usado para cada prefijo, permitiendo generar custom, custom-1, custom-2, etc. de forma más eficiente y con menos dependencia del mecanismo de locking por caché.
