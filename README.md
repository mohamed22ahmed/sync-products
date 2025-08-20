# Product Sync Task

A Laravel application that fetches and synchronizes products from a public API to a local database.

## Features

- 🔄 **API Integration**: Fetches products from the FakeStore API (https://fakestoreapi.com/products)
- 📦 **Batch Processing**: Processes products in configurable batches for optimal performance
- 💾 **Database Sync**: Automatically creates/updates products and categories in the local database
- 🎯 **Smart Updates**: Identifies existing products and updates them instead of creating duplicates
- 🖥️ **Web Dashboard**: Beautiful web interface for monitoring and controlling the sync process
- 🧪 **Comprehensive Testing**: Full test coverage for all functionality
- 📊 **Real-time Status**: Live status updates and detailed sync results

## Requirements

- PHP 8.2+
- Laravel 12.0+
- MySQL/PostgreSQL/SQLite
- Composer

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd sync-products-task
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run migrations:
```bash
php artisan migrate
```

## Usage

### Command Line Interface

#### Basic Sync
```bash
php artisan products:sync
```

#### Custom Batch Size
```bash
php artisan products:sync --batch-size=20
```

#### Custom API URL
```bash
php artisan products:sync --api-url=https://custom-api.com/products
```

### Web Dashboard

1. Start the Laravel development server:
```bash
php artisan serve
```

2. Visit the sync dashboard:
```
http://localhost:8000/sync-dashboard
```

3. Use the web interface to:
   - Set batch size
   - Configure custom API URL
   - Start/stop sync processes
   - Monitor sync status
   - View detailed results

### HTTP API

#### Sync Products
```bash
POST /api/products/sync
Content-Type: application/json

{
    "batch_size": 15,
    "api_url": "https://custom-api.com/products"
}
```

#### Get Status
```bash
GET /api/products/status
```

## How It Works

### 1. Fetch and Process Products

The system follows these steps:

1. **API Request**: Makes an HTTP request to the configured API endpoint
2. **Data Validation**: Validates the received product data
3. **Batch Processing**: Splits products into configurable batches
4. **Database Operations**: Processes each batch within a transaction

### 2. Product Processing

For each product:

1. **Category Management**: Creates or finds the associated category
2. **Product Identification**: Checks if the product already exists (using title as unique identifier)
3. **Data Update**: Updates existing products or creates new ones
4. **Relationship Linking**: Establishes proper relationships between products and categories

### 3. Batch Processing

- **Configurable Batch Size**: Default is 10 products per batch
- **Transaction Safety**: Each batch is processed within a database transaction
- **Error Handling**: Failed batches don't affect successful ones
- **Progress Tracking**: Detailed logging and status updates

## Configuration

### Environment Variables

```env
# API Configuration
PRODUCT_API_URL=https://fakestoreapi.com/products
PRODUCT_BATCH_SIZE=10

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Service Configuration

The `ProductSyncService` can be configured programmatically:

```php
use App\Services\ProductSyncService;

$syncService = new ProductSyncService();
$syncService->setBatchSize(25);
$syncService->setApiUrl('https://custom-api.com/products');
$results = $syncService->syncAllProducts();
```

## Database Schema

### Products Table
- `id` - Primary key
- `title` - Product title (unique identifier)
- `price` - Product price (decimal)
- `description` - Product description
- `image` - Product image URL
- `category_id` - Foreign key to categories
- `rating` - JSON field for rating data
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

### Categories Table
- `id` - Primary key
- `name` - Category name
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

## Testing

Run the test suite:

```bash
php artisan test
```

Or run specific tests:

```bash
php artisan test --filter=ProductSyncTest
```

## Monitoring and Logging

The system provides comprehensive logging:

- **Sync Start/Completion**: Logs when sync processes begin and end
- **Batch Processing**: Detailed logs for each batch
- **Error Handling**: Logs all errors with context
- **Performance Metrics**: Tracks processing times and statistics

Logs are stored in `storage/logs/laravel.log`

## Error Handling

The system handles various error scenarios:

- **API Failures**: Network issues, timeouts, and HTTP errors
- **Database Errors**: Connection issues and constraint violations
- **Data Validation**: Invalid or malformed product data
- **Batch Failures**: Individual batch failures don't stop the entire process

## Performance Considerations

- **Batch Processing**: Configurable batch sizes for optimal memory usage
- **Database Transactions**: Ensures data consistency
- **Connection Pooling**: Efficient HTTP client usage
- **Memory Management**: Processes data in chunks to avoid memory issues

## Security Features

- **Input Validation**: All API inputs are validated
- **SQL Injection Protection**: Uses Laravel's Eloquent ORM
- **XSS Protection**: Output is properly escaped
- **Rate Limiting**: Built-in Laravel rate limiting support

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
