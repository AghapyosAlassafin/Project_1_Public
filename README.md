# Example Laravel API (Auth + Roles + Email Codes)

This project implements a token-based authentication API with email verification codes, roles, and admin-controlled moderator accounts.

Important features

- Users table extended with `user_id`, `phone`, `profile_image`, and `role`.
- Roles: `user`, `admin`, `tourist-site-moderator`, `party-trip-moderator`.
- Registration requires email verification via a 6-digit code.
- Password reset uses a 6-digit code sent to registered email.
- Admin account is seeded (single admin). Admin can create moderators and delete/update users.
- Token-based authentication: tokens are returned as `{id}|{plain}` and must be sent as `Authorization: Bearer {id}|{plain}`.

Quick setup

1. Copy `.env.example` to `.env` and configure your database and mail settings.

2. Install dependencies and generate app key:

```bash
composer install
php artisan key:generate
```

3. Run migrations and seed the default admin:

```bash
php artisan migrate --seed
```

4. Configure admin credentials (optional):

- `ADMIN_EMAIL` default: `admin@example.com`
- `ADMIN_PASSWORD` default: `admin123`

API endpoints (JSON)

- `POST /api/register` {name, email, password, phone?, profile_image?} -> creates user and sends verification code
- `POST /api/verify` {email, code} -> verify registration code and activate account
- `POST /api/login` {email, password} -> returns `token` (use `Authorization: Bearer {token}` in requests)
- `POST /api/password/request` {email} -> send password reset code
- `POST /api/password/reset` {email, code, password} -> reset password

Protected endpoints (requires `Authorization: Bearer {id}|{token}`)

- `POST /api/logout` -> invalidate current token
- `GET /api/me` -> get profile
- `PUT /api/me` {name?, phone?, profile_image?} -> update profile (moderators cannot update themselves)
- `POST /api/me/change-password` {email, code, password} -> change password using code

Admin endpoints (requires admin token)

- `POST /api/admin/moderators` {name, email, password, role} -> create a moderator (`tourist-site-moderator` or `party-trip-moderator`)
- `PUT /api/admin/users/{id}` -> update any user
- `DELETE /api/admin/users/{id}` -> delete user

Notes

- Ensure your mail settings are configured in `.env` to send verification codes.
- Tokens are returned only once when created; store them securely.
  <p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
