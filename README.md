# PingCRM - Modern Laravel Example Application

PingCRM is a demo application built with Laravel 12, Inertia.js v2, React 19, and Tailwind CSS v4. It demonstrates how to build modern, full-stack applications with these technologies while showcasing many advanced Laravel features.

## Features
- **[Laravel 12](https://laravel.com/)**: The latest version of Laravel
- **[Inertia.js v2](https://inertiajs.com/)**: With SSR
- **[React 19](https://react.dev/)**: The latest version of React
- **[Tailwind CSS v4](https://tailwindcss.com/)**: With shadcn
- **[TypeScript](https://www.typescriptlang.org/)**
- **[Internationalization](https://www.i18next.com/)**: i18next for seamless multi-language support (EN/FR)
- Laravel Features:
  - Queue processing with [Horizon](https://laravel.com/docs/12.x/horizon)
  - [Task scheduling](https://laravel.com/docs/12.x/scheduling)
  - Real-time websockets with [Reverb](https://laravel.com/docs/12.x/reverb)
  - [Octane](https://laravel.com/docs/12.x/octane)

## Backend Infrastructure
- **[MariaDB](https://mariadb.org/)**
- **[Valkey](https://valkey.io/)**: Redis-compatible key-value store
- **[FrankenPHP](https://frankenphp.dev/)**: PHP 8.5

## Getting Started

The recommended way to run this project is using [Fadogen](https://docs.fadogen.app) — a free and open-source macOS app (Apple Silicon) that handles the entire PHP application lifecycle, from local development to production deployment.

## Deployment
The project includes a GitHub Actions workflow for deploying to your own server using Cloudflare Tunnels. I personally host the live demo version on a Raspberry Pi.

## Credits
The [original Vue.js](https://github.com/inertiajs/pingcrm) version was created by [Jonathan Reinink](https://github.com/reinink).
A [React version](https://github.com/liorocks/pingcrm-react) was later created by [Lior Rocks](https://github.com/liorocks), which served as inspiration for this implementation.
