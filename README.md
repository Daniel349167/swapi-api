
# 🚀 API REST en Laravel con Autenticación y Consumo de SWAPI

Este proyecto tiene como objetivo desarrollar una API REST en Laravel que implemente autenticación segura, una estructura modular, consumo eficiente de la API externa SWAPI, gestión de roles, pruebas unitarias y almacenamiento optimizado en PostgreSQL.

## 🛠️ Configuración y despliegue

Sigue estos pasos precisos para desplegar el proyecto localmente:

### 1. 🔗 Clonar el repositorio

```bash
git clone https://github.com/Daniel349167/swapi-api.git
cd swapi-api
```

### 2. ⚙️ Crear archivo de configuración .env

```bash
cp .env.example .env
```

Configura la base de datos PostgreSQL en `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laraveluser
DB_PASSWORD=secret
```

### 3. 🐳 Construir imágenes Docker

```bash
docker-compose build
```

### 4. 🚀 Iniciar contenedores Docker

```bash
docker-compose up -d
```

Verifica que los contenedores estén activos:

```bash
docker-compose ps
```

### 5. 📦 Instalar dependencias

```bash
docker-compose run --rm app composer install
```

### 6. 🔑 Generar clave de aplicación Laravel

```bash
docker-compose run --rm app php artisan key:generate
```

### 7. 🗃️ Ejecutar migraciones

```bash
docker-compose run --rm app php artisan migrate
```

### 8. ✅ Ejecutar pruebas unitarias

```bash
docker-compose run --rm app php artisan test
```

### 9. 🌐 Iniciar servidor Laravel (Swagger)

```bash
docker-compose run --rm -p 8000:8000 app php artisan serve --host=0.0.0.0
```

Accede a la documentación Swagger en:

```bash
http://localhost:8000/api/documentation
```
