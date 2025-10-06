# PingCRM - Modern Laravel Example Application

PingCRM is a demo application built with Laravel 12, Inertia.js v2, React 19, and Tailwind CSS v4. It demonstrates how to build modern, full-stack applications with these technologies while showcasing many advanced Laravel features.

## Features
- **[Laravel 12](https://laravel.com/)**: The latest version of Laravel
- **[Inertia.js v2](https://inertiajs.com/)**: With SSR
- **[React 19](https://react.dev/)**: The latest version of React
- **[Tailwind CSS v4](https://tailwindcss.com/)**: With shadcn
- **[TypeScript](https://www.typescriptlang.org/)**
- **[Bun](https://bun.sh/)**: npm replacement
- **[Internationalization](https://www.i18next.com/)**: i18next for seamless multi-language support (EN/FR)
- Laravel Features:
  - Queue processing with [Horizon](https://laravel.com/docs/12.x/horizon)
  - [Task scheduling](https://laravel.com/docs/12.x/scheduling)
  - Real-time websockets with [Reverb](https://laravel.com/docs/12.x/reverb)
  - [Octane](https://laravel.com/docs/12.x/octane)

## Backend Infrastructure
- **[MariaDB](https://mariadb.org/)**
- **[Valkey](https://valkey.io/)**: Redis-compatible key-value store
- **[FrankenPHP](https://frankenphp.dev/)**: PHP 8.4

## Getting Started
You can use either DDEV (Docker) or Herd to run this project for local development.

### Option 1: DDEV (Docker)
#### Prerequisites
- [Install DDEV](https://ddev.readthedocs.io/en/stable/#installation)

#### Setup
1. Clone this repository:
   ```bash
   git clone https://github.com/fouteox/pingcrm-react-inertia-laravel.git
   cd pingcrm-react-inertia-laravel
   ```

2. Start the application:
   ```bash
   ddev start && ddev launch
   ```

That's it! This single command will:
- Install PHP dependencies (Composer)
- Install Node.js dependencies (Bun)
- Run database migrations and seeders
- Start Horizon for queue processing
- Configure and start the task scheduler
- Start Reverb for websocket functionality
- Launch the application in your browser

### Option 2: Herd
#### Prerequisites
- [Install Herd](https://herd.laravel.com/)
- Have a running MariaDB instance (or MySQL)
- Have Redis installed for queues

#### Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/fouteox/pingcrm-react-inertia-laravel.git
   cd pingcrm-react-inertia-laravel
   ```

2. Install PHP dependencies:
   ```bash
   composer run setup
   ```

This will automatically set up your environment file, generate an application key, run database migrations, seed the database, and install JavaScript dependencies.

#### Launching the Application

After installation, launch the application with:
   ```bash
   composer run dev
   ```

This single command will start both Horizon for queue processing and the frontend development server simultaneously.

## Deployment
The project includes a GitHub Actions workflow for deploying to your own server using Cloudflare Tunnels. I personally host the live demo version on a Raspberry Pi.
A detailed tutorial on self-hosting this application will be available soon, documenting the process of setting up a production environment on low-cost hardware with Cloudflare Tunnel for secure remote access.

## Credits
The [original Vue.js](https://github.com/inertiajs/pingcrm) version was created by [Jonathan Reinink](https://github.com/reinink).
A [React version](https://github.com/liorocks/pingcrm-react) was later created by [Lior Rocks](https://github.com/liorocks), which served as inspiration for this implementation.
