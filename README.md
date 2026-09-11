# BGFC Inventory Management System

A production-style school inventory application built for the three-part CRUD examination: a real MySQL-backed web application, a manual Ubuntu VM deployment, and a clean Docker Compose deployment of the same application.

## Features

- Session login/logout with bcrypt password verification and role-based access control
- Administrator, Inventory Officer, and Staff permissions
- Dashboard statistics, recent activity, Chart.js stock overview, and low-stock alerts
- Full Items CRUD with search, category/status filters, detail view, validation, and permanent deletion for items without transaction history
- Categories, suppliers, and departments CRUD with soft deletion
- Atomic Stock In, Stock Out, and Stock Adjustment operations using MySQL transactions and row locks
- Live inventory, transaction history with filters, CSV/printable reports, users, and audit logs
- Friendly 403, 404, 500, invalid login, duplicate code, validation, and insufficient-stock handling
- Responsive Bootstrap 5 UI with custom BGFC navy/gold styling

## Technology

Node.js 22, Express, EJS, Bootstrap 5, MySQL 8.4, `mysql2`, `express-session`, bcrypt, dotenv, connect-flash, Chart.js, Docker, and Docker Compose.

## Project structure

```text
src/
  controllers/    Application logic
  database/       MySQL connection pool
  middleware/     Authentication, roles, auditing
  routes/         Express routes
  views/          EJS pages and partials
public/           Custom CSS and browser JavaScript
database/         Idempotent schema and seed SQL
Dockerfile        Node 22 application image
docker-compose.yml Application + MySQL services
```

The examination application starts from `src/app.js` through `npm start`. Any legacy prototype files are not part of the Node runtime.

## Local installation

Prerequisites: Node.js 22+, npm, and MySQL 8+.

```bash
npm install
cp .env.example .env
```

Set `.env` (never commit it):

```dotenv
PORT=3000
NODE_ENV=development
DB_HOST=localhost
DB_PORT=3306
DB_NAME=bgfc_inventory
DB_USER=bgfc_user
DB_PASSWORD=replace_with_a_strong_password
SESSION_SECRET=replace_with_a_long_random_secret
MYSQL_ROOT_PASSWORD=only_used_by_docker_compose
```

Create the database/user and import data:

```sql
CREATE DATABASE bgfc_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bgfc_user'@'localhost' IDENTIFIED BY 'replace_with_a_strong_password';
GRANT ALL PRIVILEGES ON bgfc_inventory.* TO 'bgfc_user'@'localhost';
FLUSH PRIVILEGES;
```

```bash
mysql -u bgfc_user -p bgfc_inventory < database/schema.sql
mysql -u bgfc_user -p bgfc_inventory < database/seed.sql
npm start
```

Open <http://localhost:3000>.

On the configured Windows/XAMPP computer, `start-bgfc.cmd` can be
double-clicked to start the Node server and open the login page. The Node
application must be started again after Windows restarts.

## Default examination login

- Username: `admin`
- Password: `password`

The seed contains only a bcrypt hash, never plaintext password data. This predictable account is for an isolated exam demonstration only. Log in, edit the administrator, and set a strong password before any shared or production use.

## Manual Ubuntu VM deployment

Update the VM and install the runtime/database:

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install nodejs npm mysql-server git -y
node -v
npm -v
mysql --version
sudo systemctl enable mysql
sudo systemctl start mysql
```

Ubuntu's repository may provide an older Node release. If `node -v` is below 22, install Node 22 from an approved NodeSource or official Node distribution before continuing.

Create the local-only MySQL identity:

```bash
sudo mysql
```

```sql
CREATE DATABASE bgfc_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bgfc_user'@'localhost' IDENTIFIED BY 'StrongPasswordHere';
GRANT ALL PRIVILEGES ON bgfc_inventory.* TO 'bgfc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Install and configure the application:

```bash
git clone YOUR_REPOSITORY bgfc-inventory
cd bgfc-inventory
npm ci --omit=dev
cp .env.example .env
nano .env
mysql -u bgfc_user -p bgfc_inventory < database/schema.sql
mysql -u bgfc_user -p bgfc_inventory < database/seed.sql
npm start
```

Use `DB_HOST=localhost`, the created password, a long random `SESSION_SECRET`, and `PORT=3000`. Browse to `http://VM-IP:3000`.

For a persistent service, create `/etc/systemd/system/bgfc-inventory.service`:

```ini
[Unit]
Description=BGFC Inventory
After=network.target mysql.service

[Service]
Type=simple
User=ubuntu
WorkingDirectory=/home/ubuntu/bgfc-inventory
ExecStart=/usr/bin/npm start
Restart=on-failure
Environment=NODE_ENV=development

[Install]
WantedBy=multi-user.target
```

Adjust the user/path to the VM, then run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now bgfc-inventory
sudo systemctl status bgfc-inventory
```

Allow only SSH and the app. Do not expose MySQL port 3306:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 3000/tcp
sudo ufw enable
sudo ufw status
```

## Docker deployment

Create `.env` with strong values:

```dotenv
PORT=3000
DB_PASSWORD=StrongDatabasePasswordHere
MYSQL_ROOT_PASSWORD=DifferentStrongRootPasswordHere
SESSION_SECRET=LongRandomSessionSecretHere
```

Then:

```bash
docker compose build
docker compose up -d
docker compose ps
docker compose logs -f app
```

Open <http://localhost:3000>. MySQL has no host port mapping. The health check holds application startup until the database is ready, and SQL files initialize a new volume automatically.

### Required clean-build test

Warning: the first command deletes the Docker database volume and its data.

```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose ps
```

Both `bgfc_inventory_app` and `bgfc_inventory_db` should be running/healthy. No file editing inside either container is required.

## Security notes

- Credentials, port, and session secret are read from environment variables; startup fails when a required value is missing.
- `.env` is excluded from Git and Docker build context.
- Passwords use bcrypt; SQL values are parameterized.
- Stock mutations use `BEGIN`, `SELECT ... FOR UPDATE`, validation, and `COMMIT`/`ROLLBACK`.
- Items without transaction history can be permanently deleted. Items with transactions are protected so historical inventory records cannot be corrupted; master data uses archiving.
- Session cookies are HTTP-only and SameSite=Lax. Set `NODE_ENV=production` only when serving behind HTTPS because it enables secure cookies.
- The default in-memory session store is suitable for the single-instance examination deployment. Use a persistent MySQL/Redis session store before scaling to multiple app instances.

## Troubleshooting

- **Database unavailable:** verify MySQL is running, `.env` values match the user grant, and Docker reports the DB healthy.
- **Access denied:** ensure `bgfc_user` has privileges on `bgfc_inventory.*` and `DB_HOST` is `db` in Compose but `localhost` on the VM.
- **Login redirects repeatedly:** do not set `NODE_ENV=production` over plain HTTP; secure cookies require HTTPS.
- **Port in use:** change host `PORT` in `.env`; the application continues to listen on container port 3000.
- **Seed did not rerun:** MySQL initialization scripts only run for an empty volume. Use `docker compose down -v` only when intentionally resetting all Docker data.
- **View logs:** use `docker compose logs app db` or `journalctl -u bgfc-inventory -e` on Ubuntu.

## Examination flow

Login → Dashboard → Items → Create/View/Edit/Delete → Stock In → Stock Out → Inventory/low-stock state → Transactions → Reports. For VM evidence, show versions, `.env` variable names (hide values), service startup, and browser CRUD. For Docker evidence, show the Dockerfile, Compose file, clean build, both containers, then repeat CRUD.
