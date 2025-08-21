<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Logs Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .status-success {
            color: #28a745;
        }
        .status-failed {
            color: #dc3545;
        }
        .section {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .metric {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .metric:last-child {
            border-bottom: none;
        }
        .metric-label {
            font-weight: bold;
            color: #495057;
        }
        .metric-value {
            color: #6c757d;
        }
        .error-section {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔄 Product Sync Report</h1>
        <p>Hello {{ $adminName }},</p>
        <p>Product synchronization has been completed. Here are the details:</p>
    </div>

    <div class="section">
        <h3>📊 Sync Summary</h3>
        <div class="metric">
            <span class="metric-label">Sync Type:</span>
            <span class="metric-value">{{ ucfirst(str_replace('_', ' ', $syncLog->sync_type)) }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Status:</span>
            <span class="metric-value status-{{ $syncLog->status === 'completed' ? 'success' : 'failed' }}">
                {{ ucfirst($syncLog->status) }}
            </span>
        </div>
        <div class="metric">
            <span class="metric-label">Total Products Fetched:</span>
            <span class="metric-value">{{ $syncLog->total_products_fetched }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Products Created:</span>
            <span class="metric-value">{{ $syncLog->products_created }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Products Updated:</span>
            <span class="metric-value">{{ $syncLog->products_updated }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Products Skipped:</span>
            <span class="metric-value">{{ $syncLog->products_skipped }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Products Failed:</span>
            <span class="metric-value">{{ $syncLog->products_failed }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Total Batches:</span>
            <span class="metric-value">{{ $syncLog->total_batches }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Duration:</span>
            <span class="metric-value">{{ $syncLog->duration_formatted }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Started At:</span>
            <span class="metric-value">{{ $syncLog->started_at->format('Y-m-d H:i:s') }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Completed At:</span>
            <span class="metric-value">
                {{ $syncLog->completed_at ? $syncLog->completed_at->format('Y-m-d H:i:s') : 'N/A' }}
            </span>
        </div>
    </div>

    @if($batchStatus)
    <div class="section">
        <h3>📦 Batch Details</h3>
        <div class="metric">
            <span class="metric-label">Batch ID:</span>
            <span class="metric-value">{{ $batchStatus['id'] }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Total Jobs:</span>
            <span class="metric-value">{{ $batchStatus['total_jobs'] }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Pending Jobs:</span>
            <span class="metric-value">{{ $batchStatus['pending_jobs'] }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Failed Jobs:</span>
            <span class="metric-value">{{ $batchStatus['failed_jobs'] }}</span>
        </div>
        <div class="metric">
            <span class="metric-label">Progress:</span>
            <span class="metric-value">{{ $batchStatus['progress_percentage'] }}%</span>
        </div>
    </div>
    @endif

    @if($syncLog->status === 'failed' && $syncLog->error_message)
    <div class="section error-section">
        <h3>❌ Error Details</h3>
        <div class="metric">
            <span class="metric-label">Error Message:</span>
            <span class="metric-value">{{ $syncLog->error_message }}</span>
        </div>
    </div>
    @endif

    @if($syncLog->success_rate > 0)
    <div class="section">
        <h3>📈 Success Metrics</h3>
        <div class="metric">
            <span class="metric-label">Success Rate:</span>
            <span class="metric-value">{{ $syncLog->success_rate }}%</span>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for using our product sync system!</p>
        <p><small>This email was sent automatically from {{ config('app.name') }}</small></p>
    </div>
</body>
</html>
