# 🏢 TradeCore B2B — High-Concurrency Wholesale Marketplace

An enterprise-grade B2B Wholesale Marketplace engineered with **Laravel 11**, designed to manage high-volume commercial procurement, real-time supply chain logistics, and multi-vendor financial settlements.

---

## ⚡ Core Technical Features

* **Dynamic Tiered Wholesale Pricing**: Volume-based automated price tier evaluation cached via **Redis** to eliminate redundant database reads under high concurrency.
* **Multi-Vendor Split Fulfillment**: Single checkout baskets automatically segment order items into vendor-specific shipments with dedicated consignments, tracking numbers, and warehouse allocations.
* **Atomic Concurrency & Inventory Locks**: Race conditions during stock reservations and withdrawals are prevented using database row-level locking (`lockForUpdate()`).
* **Real-Time WebSocket Tracking**: Order tracking and logistics status updates broadcast instantly to buyers and vendors using **Laravel Reverb** and **Laravel Echo**.
* **Asynchronous Streaming Batch Import**: High-volume inventory uploads handled via queued chunked jobs using **Laravel Job Batching** with front-end polling.
* **Automated B2B Tax Invoicing**: Automated PDF tax invoice engine (**DomPDF**) detailing commercial STRN/NTN credentials and a 5% provincial GST tax bifurcation.
* **Vendor Escrow & Payout Desk**: Double-entry financial settlement desk allowing vendors to claim funds post-fulfillment, with an administrative review and wire clearing workflow.
* **Automated Integration Test Suite**: Complete test coverage via **PHPUnit 12** verifying the pricing engine, order splitting, stock allocation, and escrow integrity.

---

## 🛠 Tech Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend Framework** | Laravel 13 (PHP 8.2+) |
| **Frontend & Reactivity** | Blade Components, Tailwind CSS, Alpine.js |
| **Real-time WebSockets** | Laravel Reverb, Laravel Echo, Pusher JS |
| **Cache & Queue Driver** | Redis |
| **Primary Database** | MySQL 8.0 (Testing via SQLite In-Memory) |
| **Document Generation** | DomPDF (`barryvdh/laravel-dompdf`) |
| **Testing** | PHPUnit 12 |

---

## 🚀 Installation & Local Setup

### 1. Clone the Repository
```bash
git clone [https://github.com/your-username/tradecore-b2b.git](https://github.com/your-username/tradecore-b2b.git)
cd tradecore-b2b

2. Install Dependencies
composer install
npm install

3.Configure Environment
cp .env.example .env
php artisan key:generate

4.Ensure your .env is configured for MySQL, Redis, and Reverb:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tradecore_db
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=tradecore-b2b
REVERB_APP_KEY=tradecore-key
REVERB_APP_SECRET=tradecore-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

5.Database Migrations & Seeders
php artisan migrate --seed

Pre-configured Seed Accounts:
Admin / Finance: admin@tradecore.test | password

Vendor Supplier: vendor@tradecore.test | password

Corporate Buyer: buyer@tradecore.test | password

6.Compile Frontend Assets
npm run build

⚙️ Running Background Daemons
For full operational functionality, run the following three processes concurrently:

# 1. Main HTTP Application Server
php artisan serve

# 2. Reverb WebSocket Daemon
php artisan reverb:start --debug

# 3. Asynchronous Queue Worker (Inventory & Invoices)
php artisan queue:work --tries=3 --timeout=120

🧪 Running Automated Tests
Execute the complete core integration pipeline test suite:

php artisan test --filter=B2BMarketplaceCorePipelineTest

Expected result:
PASS  Tests\Feature\B2BMarketplaceCorePipelineTest
  ✓ it accurately computes wholesale tiered pricing with redis cache
  ✓ it splits order into distinct vendor shipments and reserves inventory batches
  ✓ it handles vendor payout escrow deduction and admin settlement workflow
  ✓ it refunds escrow funds when admin rejects payout

  Tests:    4 passed (15 assertions)
  Duration: 0.22s

📄 License
This project is open-sourced software licensed under the MIT License.
