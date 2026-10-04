# 🔗 React + Laravel API

Contoh arsitektur **SPA React yang terpisah dari backend REST API Laravel** — studi kasus CRUD post sederhana.

```
react-laravel-api/
├── laravel-10-api/    # Backend: REST API (Laravel 12)
└── react-js-crud/     # Frontend: SPA React 18 + Vite + React Router
```

## Menjalankan Backend (Laravel 12)

```bash
cd laravel-10-api
composer install
cp .env.example .env
php artisan key:generate
# sesuaikan koneksi database di .env
php artisan migrate
php artisan serve        # API di http://localhost:8000
```

## Menjalankan Frontend (React)

```bash
cd react-js-crud
npm install
npm run dev              # http://localhost:5173
```

Frontend memanggil API Laravel via Axios (endpoint CRUD `/api/posts`).

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 12 · MySQL · PHP ≥ 8.2 |
| Frontend | React 18 · Vite · React Router 6 · Axios |

## Riwayat

Dibangun tahun 2023 dengan Laravel 10 (nama repo semula: react-laravel-10-api); backend dipugar ke **Laravel 12** (Oktober 2026) — kompatibel PHP 8.2–8.5, migrasi & test terverifikasi, build React tervalidasi.
