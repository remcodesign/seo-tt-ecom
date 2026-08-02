# SEO TT Ecom — Local Setup (macOS + DDEV)

This guide covers the complete setup to get this project running locally on **macOS** using **DDEV** with a Docker provider.

> **System requirements:** macOS Sonoma (14) or later, 8 GB RAM, 256 GB storage.

---

## Prerequisites

### 1. Docker Provider (choose one)

DDEV supports multiple Docker providers on macOS. Pick one:

| Provider | Recommendation | Notes |
| ---------- | --------------- | ------- |
| **OrbStack** | ⭐ **Fastest, lightest** | `brew install orbstack`. Requires a license for professional use, but has a generous free tier. Significantly faster than Docker Desktop on Apple Silicon. |
| **Docker Desktop** | Most common | Easy setup, GUI. |

#### Option A: Docker Desktop

1. Download from [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop/)
2. Install the `.dmg` and move Docker to `/Applications`
3. Open Docker Desktop and complete the onboarding
4. Recommended settings (Apple Silicon Macs):
   - **Settings → General → Virtual Machine Manager**: select **Apple Virtualization** (required for Rosetta support)
   - **Settings → General**: optionally enable **"Use Rosetta for x86/64/amd64 emulation on Apple Silicon"** — accelerates Intel-image containers but is **no longer required** (since Docker Desktop 4.30.0)
   - **Settings → Resources**: allocate at least **4 GB RAM** and **2 CPUs**
5. Verify in Terminal:

```bash
docker --version
docker info
```

#### Option B: OrbStack (recommended for speed)

```bash
brew install orbstack
```

Then open the OrbStack app and complete the setup. OrbStack is lighter, starts faster, and integrates seamlessly with DDEV.

---

### 2. DDEV

> **System requirements:** macOS Sonoma (14) or higher, 8 GB RAM, 256 GB storage.

```bash
# Install via Homebrew (recommended on macOS)
brew install ddev/ddev/ddev

# Or download from https://github.com/ddev/ddev/releases

# Verify
ddev --version
```

### 3. mkcert (local SSL certificates)

```bash
brew install mkcert
mkcert -install
```

---

## Full Setup (fresh clone → running)

Run these steps **in order** after cloning the repo.

### 1. Clone the repository

```bash
git clone <repo-url>
cd seo_tt_ecom
```

### 2. Start DDEV

```bash
ddev start
```

This reads `.ddev/config.yaml` and provisions:

- PHP 8.4 + nginx-fpm
- MariaDB 11.8 (port `54330` on host)
- Node.js + npm
- Composer 2
- xdebug (enabled)

> **First start** will pull Docker images — this takes a few minutes.

### 3. Set up the environment file

```bash
cp .env.example .env
```

Then **update `.env`** for DDEV's MariaDB (replace the SQLite defaults):

```dotenv
APP_URL=https://seo-tt-ecom.ddev.site

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db
```

> DDEV automatically injects these into the container, but the `.env` file must match so local Artisan commands work.

### 4. Install PHP dependencies

```bash
ddev composer install
```

### 5. Generate application key

```bash
ddev artisan key:generate
```

### 6.1 Install frontend dependencies

- when not working - delete `(folder) node_modules + (file) package-lock.json`, then follow again:

```bash
npm install
..
npm run build
npm run dev
```

### 6.2 Let playwright work

> LOCAL install playwright packages, all from local (!) not from DDEV

- when not working - delete `(folder) node_modules + (file) package-lock.json`, then follow again:

```bash
npm install
npm install playwright@latest && npx playwright install
..
npm run test:browser 
```

### 7. Build frontend assets

```bash
npm run build
```

### 8. Run database migrations & seeders

```bash
ddev artisan migrate
ddev artisan db:seed

# or in one command

ddev composer db_refresh
```

### 9. Generate TypeScript types (from Spatie Data DTOs)

```bash
ddev artisan typescript:transform
```

### 10. Launch the site

```bash
ddev launch
```

Your browser opens `https://seo-tt-ecom.ddev.site` 🎉

---

## Daily Workflow (some duplication)

### Start / stop

```bash
ddev start
ddev stop
ddev poweroff   # stops all DDEV projects
```

### Run Artisan commands

```bash
ddev artisan migrate
ddev artisan make:controller Blog/PostController
ddev artisan route:list
```

### Run tests

```bash
# All tests
ddev pest

# Specific file
ddev pest tests/Feature/Api/Auth/UserTest.php

# Filtered
ddev pest tests/Feature/Api/Auth/UserTest.php --filter="it_registers_a_user_successfully"
```

### Refresh database

> look at `### 8. Run database migrations & seeders`

### Frontend dev (Vite hot-reload)

```bash
npm run dev
```

### Full format + static analysis + tests

```bash
ddev composer format
# or (without coverage) - for LLM agents
ddev composer format-basic
```

### xdebug + code coverage

> better use `### Full format + static analysis + tests` .. first command

```bash
ddev xdebug on
ddev composer coverage
# HTML report → public/coverage/index.html
```

### TypeScript types

> is part of `### Full format + static analysis + tests`

```bash
ddev artisan typescript:transform
```

### update composer packages

```bash
composer require laravel/pint --dev
```

---

## Troubleshooting

### Port conflicts

If ports 80 or 443 are already in use, edit `.ddev/config.yaml`:

```yaml
router_http_port: "8080"
router_https_port: "8443"
host_db_port: "54330"
```

Then restart:

```bash
ddev restart
```

### SSL certificate warnings

```bash
mkcert -install
ddev poweroff
ddev start
```

### Reset everything

```bash
ddev stop -a
ddev start
```

### Vite manifest error

If you see `Unable to locate file in Vite manifest`, rebuild:

```bash
ddev npm run build
```

---

## Project Info

| Component | Version |
| ----------- | --------- |
| PHP | 8.4 |
| Laravel | 13.x |
| MariaDB | 11.8 |
| Node.js | (DDEV-managed) |
| Vue 3 | SPA (no Inertia) |
| Tailwind CSS | 4.x |
| Livewire | 4.x |
| Pest | 4.x |

<!-- --------------------------------------------------------------- -->

## Pre-prompt (paste at top of every new chat)

---

> **VERY IMPORTANT — read and follow these instructions in order:**

### 1. Load project context

Read these files first — they contain the project's conventions, architecture, and coding standards:

```txt
AGENTS.md
.github/instructions/workspace.instructions.md
```

### 2. Check project specs

Use the `Laravel Boost :: application-info` MCP tool to verify the current package versions and PHP version.

### 3. Activate relevant skills

This project has domain-specific skills in `.github/skills/` (e.g., `laravel-best-practices`, `livewire-development`, `pest-testing`, `tailwindcss-development`). Activate the relevant skill(s) before working in that domain.

### 4. Follow existing code style

Before creating or editing a file, check **sibling files** and **related code** for the current patterns:

- Creating a test? Look at existing tests in the same or neighboring related directory.
- Creating a service? Check other services for the same patterns.
- Creating a Vue component? Check existing components for conventions.

### 5. Core rules

- **No overengineering** — keep it clean, simple, and consistent with the existing codebase.
- **No new dependencies** without explicit approval.
- **Prefer `php artisan make:*`** for scaffolding when it fits project conventions.
- **Check for existing components** before writing new ones.
- **Use `ddev` prefix** for PHP/composer/artisan commands (`ddev artisan`, `ddev composer`, `ddev pest`), but not for frontend/local NPM commands (`npm run`)

### 6. After completing the job

Run these in order and fix any errors:

```bash
# Backend changes (Pint + Rector + TypeScript + PHPStan + Pest)
ddev composer format-basic

# Frontend + DTO changes (builds assets, catches Vite/TypeScript errors)
npm run build
```

### 7. Tests

- Update tests when the codebase changes — but first verify the code change is correct.
- No need for backward compatibility for most changes (or otherwise stated).
- Run affected tests to confirm they pass and if not fix the errors.

---

> **The job to be done:**
