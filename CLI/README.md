# OpenVK CLI Commands

This directory contains command-line utilities for OpenVK management.

## Available Commands

### cleanup-pending-uploads
Automatically removes pending photo uploads older than the specified time.

**Usage:**
```bash
# Clean up uploads older than 24 hours (default)
php openvkctl cleanup-pending-uploads

# Clean up uploads older than 1 hour
php openvkctl cleanup-pending-uploads --max-age=1

# Dry run to see what would be deleted
php openvkctl cleanup-pending-uploads --dry-run
```

**Options:**
- `--max-age`, `-a`: Maximum age in hours (default: 24)
- `--dry-run`, `-d`: Show what would be deleted without actually deleting

**Cron Setup:**
To automatically clean up pending uploads daily, add to your crontab:
```bash
# Clean up pending uploads daily at 2 AM
0 2 * * * cd /path/to/openvk && php openvkctl cleanup-pending-uploads
```

### build-images
Rebuilds photo thumbnails and image sizes.

### fetch-toncoin-transactions
Fetches Toncoin transactions for payment processing.

### upgrade
Performs database upgrades and migrations.