# Amunisi Backend (be-amunisi)

This is the backend repository for the Amunisi application, built with [Laravel 12](https://laravel.com/). 
It provides the core REST API for the application, handling user authentication, payments, document generation, and various core domain functionalities.

## Tech Stack & Key Integrations

* **Framework:** Laravel 12 (PHP 8.2+)
* **Authentication:** Laravel Sanctum (API tokens) & Laravel Socialite (OAuth)
* **Payments:** Midtrans Payment Gateway (`midtrans/midtrans-php`)
* **Document Generation:** mPDF for PDF generation (`carlos-meneses/laravel-mpdf`)
* **Excel Processing:** PhpSpreadsheet (`phpoffice/phpspreadsheet`) for imports/exports

## Knowledge Graph (Graphify)

This repository incorporates a [Graphify](https://github.com/safishamsi/graphify) knowledge graph to assist AI agents and developers in navigating the codebase.

The knowledge graph is located in the `graphify-out/` directory. It contains an interactive HTML visualization (`graphify-out/graph.html`), a structural report (`graphify-out/GRAPH_REPORT.md`), and the raw JSON graph (`graphify-out/graph.json`).

### Using Graphify

To update the graph after making code changes:
```bash
# Update incrementally
graphify update .
```

To ask the graph a question about the architecture:
```bash
graphify query "How does the Tryout session management work?"
```

## Setup & Installation

Follow these steps to set up the project locally:

```bash
# 1. Install PHP dependencies
composer install

# 2. Setup environment variables
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. (Optional) Run database seeders if needed
php artisan db:seed

# 6. Start the local development server
php artisan serve
```

## Testing

This project uses Pest / PHPUnit for testing. To run the test suite:
```bash
php artisan test
```
