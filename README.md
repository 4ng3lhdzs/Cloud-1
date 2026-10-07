# 📒 Agenda Telefónica (PHP + MySQL) con balanceador de carga en OCI

Aplicación web CRUD (Crear, Leer, Actualizar y Eliminar) con **PHP** y **MySQL**,
desplegada en **3 instancias Ubuntu con nginx** detrás de un **Load Balancer** de
Oracle Cloud, con una **única base de datos** y **login de usuarios**.

## Arquitectura

```
                    Internet
                       │
          ┌────────────┴────────────┐
          │  Balanceador01 (LB)     │  129.80.27.33  :80
          └──┬─────────┬─────────┬──┘
             │         │         │
         ┌───┴──┐  ┌───┴──┐  ┌───┴──┐
         │ Ng1  │  │ Ng2  │  │ Ng3  │   nginx + PHP-FPM + agenda (las 3 iguales)
         │.0.77 │  │.0.210│  │.0.57 │
         └───┬──┘  └───┬──┘  └───┬──┘
             │         │         │      red privada 10.0.0.0/24 (puerto 3306)
             └────► MySQL (en Ng1, 10.0.0.77) ◄────┘
```

| Instancia | IP pública      | IP privada | Rol                         |
|-----------|-----------------|------------|-----------------------------|
| Ng1       | 129.80.238.133  | 10.0.0.77  | Web (nginx+PHP) **+ MySQL** |
| Ng2       | 193.122.148.96  | 10.0.0.210 | Web (nginx+PHP)             |
| Ng3       | 129.213.123.251 | 10.0.0.57  | Web (nginx+PHP)             |

- Las 3 instancias tienen el mismo código y se conectan a MySQL por la **IP privada** de Ng1.
- MySQL solo acepta conexiones desde la red privada (`10.0.0.0/24`), nunca desde Internet.
- **Las sesiones del login se guardan en MySQL** (tabla `sesiones`). Así, aunque el
  balanceador mande cada petición a una instancia distinta, el usuario no pierde la sesión.
- El pie de página muestra **qué instancia atendió la petición** (sirve para comprobar el balanceo).

## Funcionalidades

- **Login / logout** con contraseñas cifradas (`password_hash` / `password_verify`)
- Listar todos los contactos
- Buscar por nombre, apellidos o teléfono
- Agregar un contacto nuevo
- Editar un contacto existente
- Eliminar un contacto (con confirmación)

## Requisitos

- 3 VMs en Oracle Cloud con **Ubuntu** y **nginx**
- Load Balancer con las 3 VMs como backends en el puerto 80
- **PHP 8.1** o superior con **PHP-FPM** y la extensión `pdo_mysql`
- **MySQL 8** (solo en Ng1)

```bash
# En Ng1, Ng2 y Ng3
sudo apt install nginx php-fpm php-mysql
# Solo en Ng1
sudo apt install mysql-server
```

> Los scripts de la carpeta `deploy/` instalan y configuran todo automáticamente.

## Estructura del proyecto

```
agenda/
├── index.php            # Lista y búsqueda de contactos (requiere login)
├── agregar.php          # Formulario para agregar
├── editar.php           # Formulario para editar
├── eliminar.php         # Elimina un contacto
├── login.php            # Inicio de sesión
├── logout.php           # Cierre de sesión
├── config.php           # Datos de conexión a MySQL (IP privada de Ng1)
├── css/
│   └── estilos.css      # Estilos de la aplicación
├── includes/
│   ├── conexion.php     # Conexión PDO y función e() para escapar HTML
│   ├── sesion.php       # Sesiones en MySQL, login y protección CSRF
│   ├── validar.php      # Validación de los datos del formulario
│   ├── formulario.php   # Formulario compartido (agregar / editar)
│   ├── header.php       # Encabezado HTML (usuario + botón Salir)
│   └── footer.php       # Pie de página HTML (instancia que respondió)
├── database/
│   ├── agenda.sql       # Script de instalación de la base de datos
│   └── crear_usuario.php# Crea usuarios desde la terminal
└── deploy/
    ├── servidores.conf  # IPs de las instancias y llave SSH
    ├── instalar.sh      # Instalación inicial (MySQL + nginx/PHP + código)
    ├── sync.sh          # Sincroniza el código con las 3 instancias
    ├── setup_db.sh      # (lo usa instalar.sh) configura MySQL en Ng1
    ├── setup_web.sh     # (lo usa instalar.sh) configura nginx + PHP-FPM
    └── nginx-agenda.conf# Configuración de nginx
```

## Instalación

### 0. Preparar Oracle Cloud

1. **Security List** de la subred (Networking → VCN → Subnet → Security List) → *Add Ingress Rule*:
   - Source CIDR: `10.0.0.0/24` · Protocol: TCP · Destination port: `3306`
   (el puerto 80 ya debe estar abierto, porque el balanceador marca *OK*).
2. **Load Balancer → Backend set**: las 3 instancias en el puerto 80 y *health check*
   HTTP en `/` (la página por defecto de nginx se conserva para que siga en *OK*).
3. Editar `deploy/servidores.conf` con la ruta de la **llave privada SSH** que descargó al
   crear las instancias (`SSH_KEY=...`).

### 1. Instalación inicial (una sola vez)

Desde la terminal de VS Code (en Windows usar **Git Bash**), en la carpeta del proyecto:

```bash
bash deploy/instalar.sh
```

Esto:
1. Instala MySQL en **Ng1**, lo abre solo a la red privada e importa `database/agenda.sql`.
2. Instala PHP-FPM y configura nginx en **Ng1, Ng2 y Ng3**, y comprueba que cada una llegue a MySQL.
3. Sube la agenda a las 3 instancias en `/var/www/html/agenda`.

### 2. Sincronizar cambios (cada vez que modifique el código)

```bash
bash deploy/sync.sh                 # sube a las 3 instancias
bash deploy/sync.sh 193.122.148.96  # sube solo a una
```

### 3. Abrir la aplicación (a través del balanceador)

```
http://129.80.27.33/agenda/
```

Usuario inicial: **admin** · Contraseña: **Admin2026!**

Para crear otro usuario (o cambiar una contraseña), conectarse por SSH a cualquier instancia:

```bash
php /var/www/html/agenda/database/crear_usuario.php angel "Angel Hernández"
```

### Instalación manual (sin los scripts)

<details>
<summary>Ver pasos</summary>

**En Ng1 (base de datos):**
```bash
sudo apt install -y mysql-server
sudo sed -i 's/^bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql
sudo mysql < database/agenda.sql
sudo iptables -I INPUT 1 -p tcp -s 10.0.0.0/24 --dport 3306 -j ACCEPT
sudo netfilter-persistent save
```

**En Ng1, Ng2 y Ng3 (web):**
```bash
sudo apt install -y php-fpm php-mysql
ls /run/php/                    # ver el socket, p. ej. php8.1-fpm.sock
# copiar deploy/nginx-agenda.conf a /etc/nginx/sites-available/default
# y cambiar __PHP_SOCK__ por /run/php/php8.1-fpm.sock
sudo nginx -t && sudo systemctl reload nginx
```
</details>

## Tablas

### `contactos`

| Campo            | Tipo          | Descripción                |
|------------------|---------------|----------------------------|
| `id`             | INT (PK, AI)  | Identificador              |
| `nombre`         | VARCHAR(50)   | Obligatorio                |
| `apellidos`      | VARCHAR(80)   | Obligatorio                |
| `telefono`       | VARCHAR(20)   | Obligatorio                |
| `email`          | VARCHAR(100)  | Opcional                   |
| `direccion`      | VARCHAR(150)  | Opcional                   |
| `fecha_registro` | TIMESTAMP     | Se asigna automáticamente  |

### `usuarios`

| Campo            | Tipo          | Descripción                          |
|------------------|---------------|--------------------------------------|
| `id`             | INT (PK, AI)  | Identificador                        |
| `usuario`        | VARCHAR(50)   | Único                                |
| `nombre`         | VARCHAR(100)  | Nombre para mostrar                  |
| `password_hash`  | VARCHAR(255)  | Contraseña cifrada (`password_hash`) |
| `activo`         | TINYINT(1)    | 1 = puede entrar                     |
| `ultimo_acceso`  | DATETIME      | Último login                         |

### `sesiones`

| Campo              | Tipo          | Descripción                         |
|--------------------|---------------|-------------------------------------|
| `id`               | VARCHAR(128)  | ID de sesión (cookie `AGENDASESSID`)|
| `datos`            | MEDIUMTEXT    | Datos serializados de `$_SESSION`   |
| `ultima_actividad` | DATETIME      | Expira tras 30 min sin actividad    |

## Conceptos de examen

- Conexión a MySQL **remoto** con **PDO** (IP privada de la VCN)
- **Consultas preparadas** para evitar inyección SQL
- Escapar la salida con `htmlspecialchars()` para evitar XSS
- Contraseñas con `password_hash()` / `password_verify()`
- **Sesiones compartidas en base de datos** (`SessionHandlerInterface`) para trabajar detrás de un balanceador
- `session_regenerate_id()` al iniciar sesión (evita fijación de sesión)
- Token **CSRF** en todos los formularios POST
- Validación de formularios en el servidor
- Patrón **POST / Redirect / GET** después de guardar
- Reutilizar código con `require` (encabezado, pie y formulario)

## Para revisión

1. Pestaña donde se visualice el VCN
2. Pestaña donde se prueba la aplicación (por la IP del balanceador; recargar para ver cambiar la instancia en el pie de página)
3. Pestaña de GitHub Personal con el proyecto ya modificado
4. Visual Studio Code abierto donde se modificó y publicó el proyecto.

## Nota

Este es un proyecto **educativo**. Antes de usarlo en un servidor público,
cambie las contraseñas de la base de datos y del usuario `admin`, y use HTTPS.
