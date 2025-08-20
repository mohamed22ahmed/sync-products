# Laravel Backend Challenge – Advanced Product Sync with Queued Batches

## Objective

Build a robust Laravel Artisan command that synchronizes product data from a public API into a relational database using queued jobs and batch processing. The task should evaluate your ability to design scalable backend systems using Laravel’s features.

---

## Public API: [Fake Store API](https://fakestoreapi.com/)

* Endpoint: `https://fakestoreapi.com/products`
* Method: `GET`
* Returns a JSON array of product objects

---

## Task Requirements

### 1. Fetch and Process Products

* Fetch products from the public API.
* Process them in batches.
* Each product should be inserted or updated in the local database.

### 2. Normalize Categories

* Extract categories from product data.
* Save them in a separate table.
* Associate products to categories.

### 3. Use Queued Jobs and Batching

* Use Laravel jobs to process products.
* Dispatch them using `Bus::batch()`.
* Handle batch success and failure.

### 4. Download Images

* Download product images.
* Store them locally.

### 5. Log Sync Summary

* Track number of products fetched, created, updated, skipped, failed.
* Store summary in a sync logs table.

### 6. Testing

* Add unit or feature tests for the sync logic.

### 7. Notifications

* Send a summary email or log entry to the admin after sync completes or fails.

### 8. Progress Bar

* Display a progress bar in the console while syncing.

### 9. Horizon-ready Queues

* Use separate named queues suitable for Laravel Horizon.

### 10. Schedule Sync Job

* Schedule the sync command to run periodically using Laravel’s scheduler.
* Ensure the schedule is defined clearly and can be enabled via `app/Console/Kernel.php`.

---

## Considerations

* Handle duplicate `external_id`s.
* Skip products with missing fields.
* Make the API URL configurable.
* Ensure clean and maintainable code.

---

## Evaluation Criteria

| Area                 | Expectation                          |
| -------------------- | ------------------------------------ |
| Laravel Fundamentals | Artisan, Queues, Jobs, Http, Storage |
| Code Quality         | Structure, naming, readability       |
| Relationships        | Proper Eloquent associations         |
| Error Handling       | Robust fallback logic and retries    |
| Performance          | Efficient batching and chunking      |
| Documentation        | Clear CLI/log output, clean codebase |

---

## Submission

* Clone this  Repo repository

* **Add **maher@msaaq.com** as a collaborator from the first commit.**

* Submit a GitHub repository.

* Include basic setup instructions in the README.

* Mention how long the task took you.

* Explain which parts you completed or skipped, and why.

* **Deadline: 3 calendar days from receiving the task.**
# sync-products
