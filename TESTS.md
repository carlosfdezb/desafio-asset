# Tests

> **106 tests | 357 assertions**
>
> Ejecutar con: `php vendor/bin/phpunit` o `php vendor/bin/phpunit --testdox`

---

## Feature Tests

### SlugApiTest (`tests/Feature/SlugApiTest.php`)

Pruebas de integración para los endpoints de la API de slugs.

| #   | Test                                                             | Descripción                                                                          |
| --- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| 1   | `test_index_returns_paginated_slugs`                             | `GET /api/slugs` retorna slugs paginados con estructura correcta (15 por defecto)    |
| 2   | `test_index_respects_per_page_parameter`                         | Respeta el parámetro `per_page` en la paginación                                     |
| 3   | `test_index_caps_per_page_at_100`                                | Limita el máximo de `per_page` a 100                                                 |
| 4   | `test_index_minimum_per_page_is_1`                               | Establece el mínimo de `per_page` en 1                                               |
| 5   | `test_index_returns_empty_when_no_slugs`                         | Retorna lista vacía cuando no hay slugs                                              |
| 6   | `test_index_hides_api_key_field`                                 | Oculta el campo `api_key` en la respuesta JSON                                       |
| 7   | `test_shorten_creates_slug_with_random_slug`                     | `POST /api/shorten` crea un slug aleatorio correctamente                             |
| 8   | `test_shorten_creates_slug_with_custom_slug`                     | Crea un slug con slug personalizado                                                  |
| 9   | `test_shorten_custom_slug_auto_increments_on_duplicate`          | Auto-incrementa el sufijo cuando el slug personalizado ya existe (ej: `test-slug-1`) |
| 10  | `test_shorten_with_api_key`                                      | Almacena la API key hasheada al crear un slug                                        |
| 11  | `test_shorten_with_expires_at`                                   | Crea un slug con fecha de expiración                                                 |
| 12  | `test_shorten_validation_requires_url`                           | Rechaza request sin URL (422)                                                        |
| 13  | `test_shorten_validation_rejects_invalid_url`                    | Rechaza URL inválida (422)                                                           |
| 14  | `test_shorten_validation_rejects_ftp_url`                        | Rechaza URLs con protocolo FTP (422)                                                 |
| 15  | `test_shorten_validation_rejects_short_api_key`                  | Rechaza API key menor a 8 caracteres (422)                                           |
| 16  | `test_shorten_validation_rejects_past_expires_at`                | Rechaza fecha de expiración en el pasado (422)                                       |
| 17  | `test_shorten_validation_rejects_long_custom_slug`               | Rechaza slug personalizado mayor a 100 caracteres (422)                              |
| 18  | `test_delete_slug_with_valid_api_key`                            | `DELETE /api/{slug}` elimina (soft delete) con API key válida                        |
| 19  | `test_delete_slug_returns_404_when_not_found`                    | Retorna 404 si el slug no existe                                                     |
| 20  | `test_delete_slug_returns_403_with_wrong_api_key`                | Retorna 403 con API key incorrecta                                                   |
| 21  | `test_delete_slug_returns_403_without_api_key_when_slug_has_key` | Retorna 403 si no se envía API key y el slug la requiere                             |
| 22  | `test_delete_slug_returns_403_when_slug_created_without_api_key` | Retorna 403 si el slug fue creado sin API key (no se puede eliminar)                 |
| 23  | `test_delete_validation_rejects_short_api_key`                   | Rechaza API key menor a 8 caracteres (422)                                           |

---

### StatApiTest (`tests/Feature/StatApiTest.php`)

Pruebas de integración para el endpoint de estadísticas.

| #   | Test                                                       | Descripción                                                                 |
| --- | ---------------------------------------------------------- | --------------------------------------------------------------------------- |
| 1   | `test_stats_returns_data_for_public_slug`                  | `GET /api/stats/{slug}` retorna estadísticas completas para un slug público |
| 2   | `test_stats_returns_data_with_valid_api_key`               | Retorna estadísticas con API key válida (header `X-API-Key`)                |
| 3   | `test_stats_returns_403_for_protected_slug_without_key`    | Retorna 403 para slug protegido sin API key                                 |
| 4   | `test_stats_returns_403_for_protected_slug_with_wrong_key` | Retorna 403 para slug protegido con API key incorrecta                      |
| 5   | `test_stats_returns_404_for_nonexistent_slug`              | Retorna 404 si el slug no existe                                            |
| 6   | `test_stats_returns_zero_clicks_for_slug_with_no_stats`    | Retorna 0 clicks y arrays vacíos cuando no hay estadísticas                 |
| 7   | `test_stats_clicks_per_day_structure`                      | Verifica la estructura de `clicks_per_day` (date, clicks)                   |
| 8   | `test_stats_top_referers_structure`                        | Verifica la estructura de `top_referers` (referer, clicks)                  |
| 9   | `test_stats_clicks_by_country_structure`                   | Verifica la estructura de `clicks_by_country` (country, clicks)             |

---

### QrApiTest (`tests/Feature/QrApiTest.php`)

Pruebas de integración para el endpoint de generación de QR.

| #   | Test                                             | Descripción                                                                         |
| --- | ------------------------------------------------ | ----------------------------------------------------------------------------------- |
| 1   | `test_generate_returns_svg_for_existing_slug`    | `GET /api/qr/{slug}` retorna código QR en formato SVG (Content-Type: image/svg+xml) |
| 2   | `test_generate_returns_404_for_nonexistent_slug` | Retorna 404 si el slug no existe                                                    |
| 3   | `test_generate_returns_valid_svg_content`        | El contenido de respuesta contiene una etiqueta `<svg>` válida                      |

---

### HealthApiTest (`tests/Feature/HealthApiTest.php`)

Prueba del endpoint de health check.

| #   | Test                            | Descripción                                                             |
| --- | ------------------------------- | ----------------------------------------------------------------------- |
| 1   | `test_health_returns_ok_status` | `GET /api/health` retorna status "ok", database "connected" y timestamp |

---

### RedirectTest (`tests/Feature/RedirectTest.php`)

Pruebas de la funcionalidad de redirección de URLs cortas.

| #   | Test                                              | Descripción                                                                |
| --- | ------------------------------------------------- | -------------------------------------------------------------------------- |
| 1   | `test_redirect_to_original_url`                   | `GET /{slug}` redirige a la URL original                                   |
| 2   | `test_redirect_dispatches_record_stat_job`        | La redirección despacha el job `RecordStatJob` para registrar estadísticas |
| 3   | `test_redirect_returns_404_for_nonexistent_slug`  | Retorna 404 si el slug no existe                                           |
| 4   | `test_redirect_returns_410_for_expired_slug`      | Retorna 410 (Gone) si el slug ha expirado                                  |
| 5   | `test_redirect_works_for_non_expired_slug`        | Redirige correctamente si el slug aún no ha expirado                       |
| 6   | `test_redirect_works_for_slug_without_expiration` | Redirige correctamente si el slug no tiene fecha de expiración             |

---

### RateLimitTest (`tests/Feature/RateLimitTest.php`)

Pruebas de rate limiting en la API.

| #   | Test                                           | Descripción                                                                             |
| --- | ---------------------------------------------- | --------------------------------------------------------------------------------------- |
| 1   | `test_stats_route_is_rate_limited`             | La ruta `/api/stats/{slug}` permite 60 requests por minuto y bloquea con 429 al exceder |
| 2   | `test_stats_route_returns_rate_limit_headers`  | La respuesta incluye headers `X-RateLimit-Limit` y `X-RateLimit-Remaining`              |
| 3   | `test_non_throttled_route_is_not_rate_limited` | La ruta `/api/health` no está afectada por rate limiting                                |

---

### DashboardTest (`tests/Feature/DashboardTest.php`)

Prueba de la vista del dashboard.

| #   | Test                          | Descripción                                                  |
| --- | ----------------------------- | ------------------------------------------------------------ |
| 1   | `test_dashboard_returns_view` | `GET /dashboard` retorna la vista `dashboard` con status 200 |

---

### ExampleTest (`tests/Feature/ExampleTest.php`)

Test de ejemplo de Laravel.

| #   | Test                                                 | Descripción                                |
| --- | ---------------------------------------------------- | ------------------------------------------ |
| 1   | `test_the_application_returns_a_successful_response` | Verifica que `/dashboard` responde con 200 |

---

## Unit Tests

### SlugServiceTest (`tests/Unit/SlugServiceTest.php`)

Pruebas unitarias del servicio `SlugService`.

| #   | Test                                                | Descripción                                             |
| --- | --------------------------------------------------- | ------------------------------------------------------- |
| 1   | `test_list_returns_paginated_results`               | `list()` retorna resultados paginados correctamente     |
| 2   | `test_create_slug_with_random_slug`                 | `createSlug()` genera un slug aleatorio de 8 caracteres |
| 3   | `test_create_slug_with_custom_slug`                 | Crea slug con slug personalizado                        |
| 4   | `test_create_slug_with_api_key_hashes_it`           | Hashea la API key al crear                              |
| 5   | `test_create_slug_with_expires_at`                  | Almacena la fecha de expiración                         |
| 6   | `test_custom_slug_increments_when_base_exists`      | Agrega sufijo `-1` cuando el slug base ya existe        |
| 7   | `test_custom_slug_increments_sequentially`          | Incrementa secuencialmente (`-1`, `-2`, `-3`...)        |
| 8   | `test_custom_slug_is_slugified`                     | Convierte texto a formato slug (minúsculas, guiones)    |
| 9   | `test_redirect_returns_slug_model`                  | `redirect()` retorna el modelo Slug                     |
| 10  | `test_redirect_throws_404_for_missing_slug`         | Lanza HttpException 404 si no existe                    |
| 11  | `test_redirect_throws_410_for_expired_slug`         | Lanza HttpException 410 si expiró                       |
| 12  | `test_delete_slug_with_correct_api_key`             | `deleteSlug()` realiza soft delete con API key correcta |
| 13  | `test_delete_slug_throws_404_for_missing_slug`      | Lanza 404 si no existe                                  |
| 14  | `test_delete_slug_throws_403_for_null_api_key_slug` | Lanza 403 si el slug se creó sin API key                |
| 15  | `test_delete_slug_throws_403_without_api_key`       | Lanza 403 si no se proporciona API key                  |
| 16  | `test_delete_slug_throws_403_with_wrong_api_key`    | Lanza 403 si la API key es incorrecta                   |

---

### StatServiceTest (`tests/Unit/StatServiceTest.php`)

Pruebas unitarias del servicio `StatService`.

| #   | Test                                                          | Descripción                                                         |
| --- | ------------------------------------------------------------- | ------------------------------------------------------------------- |
| 1   | `test_get_stats_for_public_slug`                              | `getStatsBySlug()` retorna estadísticas completas para slug público |
| 2   | `test_get_stats_for_protected_slug_with_valid_key`            | Retorna estadísticas con API key válida                             |
| 3   | `test_get_stats_throws_403_for_protected_slug_without_key`    | Lanza 403 sin API key para slug protegido                           |
| 4   | `test_get_stats_throws_403_for_protected_slug_with_wrong_key` | Lanza 403 con API key incorrecta                                    |
| 5   | `test_get_stats_throws_404_for_nonexistent_slug`              | Lanza 404 si el slug no existe                                      |
| 6   | `test_get_stats_returns_clicks_per_day_with_correct_format`   | Verifica formato de clicks por día (date, clicks como int)          |
| 7   | `test_get_stats_returns_top_referers_capped`                  | Verifica que top referers está limitado a 5                         |

---

### QrServiceTest (`tests/Unit/QrServiceTest.php`)

Pruebas unitarias del servicio `QrService`.

| #   | Test                                           | Descripción                                  |
| --- | ---------------------------------------------- | -------------------------------------------- |
| 1   | `test_generate_qr_returns_svg_string`          | `generateQr()` retorna un string SVG válido  |
| 2   | `test_generate_qr_throws_404_for_missing_slug` | Lanza HttpException 404 si el slug no existe |

---

### SlugRepositoryTest (`tests/Unit/SlugRepositoryTest.php`)

Pruebas unitarias del repositorio `SlugRepository`.

| #   | Test                                             | Descripción                                                            |
| --- | ------------------------------------------------ | ---------------------------------------------------------------------- |
| 1   | `test_paginate_returns_paginated_slugs`          | `paginate()` retorna paginación correcta                               |
| 2   | `test_find_by_slug_returns_slug_model`           | `findBySlug()` retorna el modelo Slug                                  |
| 3   | `test_find_by_slug_returns_null_when_not_found`  | Retorna null si no existe                                              |
| 4   | `test_find_by_slug_does_not_find_soft_deleted`   | No encuentra slugs eliminados (soft delete)                            |
| 5   | `test_find_similar_slugs_returns_matching_slugs` | `findSimilarSlugs()` retorna slugs del tipo `base`, `base-1`, `base-2` |
| 6   | `test_find_similar_slugs_includes_soft_deleted`  | Incluye slugs soft-deleted en la búsqueda de similares                 |
| 7   | `test_save_persists_slug`                        | `save()` persiste el slug en base de datos                             |
| 8   | `test_delete_soft_deletes_slug`                  | `delete()` realiza soft delete                                         |

---

### StatRepositoryTest (`tests/Unit/StatRepositoryTest.php`)

Pruebas unitarias del repositorio `StatRepository`.

| #   | Test                                                 | Descripción                                                  |
| --- | ---------------------------------------------------- | ------------------------------------------------------------ |
| 1   | `test_save_persists_stat`                            | `save()` persiste una estadística en base de datos           |
| 2   | `test_count_by_slug_id`                              | `countBySlugId()` cuenta clicks por slug correctamente       |
| 3   | `test_count_by_slug_id_returns_zero_when_no_stats`   | Retorna 0 cuando no hay estadísticas                         |
| 4   | `test_get_clicks_per_day_returns_recent_data`        | `getClicksPerDay()` retorna solo datos de los últimos 7 días |
| 5   | `test_get_top_referers_returns_correct_data`         | `getTopReferers()` retorna referers ordenados por clicks     |
| 6   | `test_get_top_referers_excludes_null_referers`       | Excluye referers null y vacíos                               |
| 7   | `test_get_top_referers_limits_to_5`                  | Limita a máximo 5 referers                                   |
| 8   | `test_get_clicks_by_country_returns_correct_data`    | `getClicksByCountry()` retorna países ordenados por clicks   |
| 9   | `test_get_clicks_by_country_excludes_null_countries` | Excluye países null                                          |
| 10  | `test_get_clicks_by_country_respects_limit`          | Respeta el parámetro de límite                               |

---

### RecordStatJobTest (`tests/Unit/RecordStatJobTest.php`)

Pruebas unitarias del job `RecordStatJob`.

| #   | Test                                                | Descripción                                                       |
| --- | --------------------------------------------------- | ----------------------------------------------------------------- |
| 1   | `test_job_creates_stat_record`                      | El job crea un registro de estadística con referer y país (GeoIP) |
| 2   | `test_job_stores_null_referer_host_when_no_referer` | Almacena null cuando no hay referer                               |
| 3   | `test_job_extracts_host_from_referer_url`           | Extrae correctamente el host de la URL referer                    |
| 4   | `test_job_handles_unknown_country`                  | Almacena "Desconocido" cuando GeoIP retorna null                  |

---

### SlugModelTest (`tests/Unit/SlugModelTest.php`)

Pruebas unitarias del modelo `Slug`.

| #   | Test                                            | Descripción                                          |
| --- | ----------------------------------------------- | ---------------------------------------------------- |
| 1   | `test_is_expired_returns_true_for_past_date`    | `isExpired()` retorna true si `expires_at` es pasada |
| 2   | `test_is_expired_returns_false_for_future_date` | Retorna false si `expires_at` es futura              |
| 3   | `test_is_expired_returns_false_when_null`       | Retorna false si `expires_at` es null                |
| 4   | `test_stats_relationship`                       | Verifica relación hasMany con Stat                   |
| 5   | `test_api_key_is_hidden_in_serialization`       | `api_key` no aparece en `toArray()` / JSON           |
| 6   | `test_soft_delete`                              | Verifica que soft delete funciona correctamente      |
| 7   | `test_expires_at_is_cast_to_datetime`           | `expires_at` se castea a Carbon                      |

---

### StatModelTest (`tests/Unit/StatModelTest.php`)

Pruebas unitarias del modelo `Stat`.

| #   | Test                                  | Descripción                                  |
| --- | ------------------------------------- | -------------------------------------------- |
| 1   | `test_slug_relationship`              | Verifica relación belongsTo con Slug         |
| 2   | `test_clicked_at_is_cast_to_datetime` | `clicked_at` se castea a Carbon              |
| 3   | `test_fillable_fields`                | Los campos fillable se asignan correctamente |
| 4   | `test_nullable_fields`                | Los campos nullable aceptan null             |

---

### ExampleTest (`tests/Unit/ExampleTest.php`)

Test de ejemplo de PHPUnit.

| #   | Test                     | Descripción                   |
| --- | ------------------------ | ----------------------------- |
| 1   | `test_that_true_is_true` | Verifica que `true` es `true` |
