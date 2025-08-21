# Product Sync System

A robust Laravel application that synchronizes product data from a public API (Fake Store API) into a relational database with advanced features including queued jobs, image downloads, progress tracking, email notifications, and scheduled operations.

## Features

### 1. **Product Synchronization**
- **API**: Fetches products from Fake Store API
- **Database**: Stores products and categories in SQLite database
- **Batch Processing**: Processes products in configurable batches

### 2. **Queued Job System**
- **Job Batching**: Used Bus::batch()
- **Background Processing**: Asynchronous product processing with queues
- **Queue**: use 'products' queue for sync operations

### 3. **Image Download & Storage**
- **Automatic Downloads**: Downloads product images during sync
- **Local Storage**: Stores images in `storage/app/public/products/`
- **Format Validation**: Supports JPG, JPEG, and PNG formats
- **Duplicate Prevention**: Skips already downloaded images

### 4. **Progress Bar**
- **Console Progress Bars**: Visual progress indicators during sync
- **Real-time Updates**: Live progress monitoring with refresh intervals
- **Status Display**: Current job counts, progress percentage, and timing
- **Final Reports**: Comprehensive completion summaries
- **Search**: here i used search to understand it and implement

### 5. **Sync Logging System**
- **Tracking**: Records all sync operations with timestamps
- **Metrics**: Duration, success rates, and batch information
- **Search**: here i used search to understand it and implement, cause i wasn't know what's logging refers to

### 6. **Email Notifications**
- **Email**: Sends emails when sync completes or fails
- **SMTP Support**: Gmail SMTP integration with custom configuration

### 7. **Scheduled Operations**
- **Daily Sync**: Automatically runs every day at 2:00 AM

### I like using services to separate the logic and just call it when want to use

### ai helped me to make tests and write readme file

### the task took me from 12 am to 6 am (about 6 hours)

### i don't implement point number 9 cause i don't know it 
- **Horizon-ready Queues**: Use separate named queues suitable for Laravel Horizon.