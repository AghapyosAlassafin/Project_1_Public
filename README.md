# tripora-backend

This project is a fully-featured RESTful backend for a tourism platform that serves a mobile application (Flutter) and a web dashboard (React). It manages trips, tourist locations, bookings, ratings, favorites, and notifications. The backend exposes two isolated API groups — `/api/mobile` for regular users and `/api/web` for admins and moderators — with custom token authentication and strict role-based access control. It also includes Firebase push notifications, Cloudinary image storage, scheduled tasks for trip lifecycle and account cleanup, and a Google Apps Script mailer for sending verification codes.

---

## Getting Started

### Prerequisites

* PHP (v8.2 or higher)
* Composer
* MySQL (or an Aiven/remote MySQL instance)
* Node.js (optional, for frontend assets)

### Installation

In your VS Code terminal — or any Bash-compatible terminal — navigate to the directory where you want to clone this project. Then run the following commands:

**Clone the repository**

```bash
git clone https://github.com/your-username/tripora-backend.git
cd tripora-backend

```

**Install dependencies**

```bash
composer install

```

**Copy the environment file**

```bash
cp .env.example .env

```

**Generate the application key**

```bash
php artisan key:generate

```

**Create the storage symlink**

```bash
php artisan storage:link

```

**Run migrations and seed the database**

```bash
php artisan migrate --seed

```

**Start the development server**

```bash
php artisan serve

```

The API will be available at `http://localhost:8000` (or the URL shown in your terminal).

---

## Features

* **Authentication & Authorization**: Custom token authentication with strict role-based access control (User, Admin, Moderator).
* **API Architecture**: Two isolated API groups — `/api/mobile` for mobile users and `/api/web` for web dashboard management.
* **Trip & Booking Management**: Complete flow for trip listings, bookings, cancellations, and ratings.
* **Push Notifications**: Integrated Firebase Cloud Messaging (FCM) with notification history tracking.
* **Media Management**: Direct image uploads and storage delivery using Cloudinary.
* **Automated Tasks**: Scheduled status updates for trip lifecycles and cleanup of unverified accounts.
* **Email Delivery**: Custom Google Apps Script mailer integration for verification codes and password resets.

---

## Building for Production

To cache the configuration and routes for production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache

```

To run pending migrations in production:

```bash
php artisan migrate --force

```

---

## Technologies Used

* **Laravel 11** — Backend framework
* **MySQL** — Relational database
* **Custom Token Authentication** — ApiToken-based auth with role middleware
* **Firebase Cloud Messaging** — Push notifications
* **Cloudinary** — Image storage and delivery
* **Google Apps Script** — Email delivery (verification & password reset)
* **cron-job.org** — External task scheduling for `schedule:run`
* **Render** — Backend deployment platform
* **Aiven** — Managed MySQL hosting
