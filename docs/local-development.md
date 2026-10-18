# Run the local Qatar ERP

This Windows development command runs the existing Laravel application on **http://127.0.0.1:8001/** and its restored MariaDB database on **127.0.0.1:3309**. PHP's development server is for local use only; use a supported production web server for client deployment.

From `D:\Projects\Qatar` in PowerShell:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-local.ps1 -CheckOnly
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-local.ps1
```

The first command performs preflight checks without starting services or writing files. The second starts missing services in hidden background windows. Repeating it reuses services only after verifying their executable, command arguments and loopback port. An unexpected process/address on either port causes an error. It never stops an existing process.

The restored database must already exist at `storage\app\private\database-restored-20261018`, with the `qualities_erp` database and its initialized MariaDB files. This command never initializes, imports, repairs, migrates, seeds or deletes data. It does not use or modify the original shared database on port **3307**. Do not run the recovery scripts for routine startup.

Required local configuration in `.env`:

```dotenv
APP_ENV=local
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3309
DB_DATABASE=qualities_erp
```

Keep the existing database credentials private. The startup script does not display or pass database credentials and does not edit `.env`. It rejects database URL overrides, inherited environment values that conflict with the settings above, and cached Laravel configuration. If configuration was cached, review `.env`, then run `D:\Project\xampp\php\php.exe artisan config:clear` before startup.

The runtime locations are `D:\Project\xampp\php\php.exe` and `D:\Project\xampp\mysql\bin\mysqld.exe`. Composer dependencies and the built frontend (`public\build\manifest.json`) must already exist. When frontend files change, run `npm run build` separately.

Startup stdout/stderr logs are stored under `storage\app\private\local-runtime`; MariaDB's own error log stays inside the restored database directory. These private paths are ignored by Git. Logs can contain request paths, so keep them out of shared screenshots or public attachments.

If startup fails, inspect the private logs and resolve the reported configuration or port issue. Do not delete database files or kill an unidentified port owner. Successful startup leaves both background services running when the PowerShell window closes.
