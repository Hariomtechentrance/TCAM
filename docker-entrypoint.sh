#!/bin/bash
set -e

DATA_DIR="/var/data"
WEB_UPLOADS="/var/www/html/uploads"

# If a Render Disk is mounted at /var/data, move uploads there for persistence
if mountpoint -q "$DATA_DIR" 2>/dev/null || [ -w "$DATA_DIR" ]; then
    mkdir -p "$DATA_DIR/uploads" "$DATA_DIR/uploads/coaches"
    chown -R www-data:www-data "$DATA_DIR"

    # Replace the uploads directory with a symlink to persistent storage
    if [ ! -L "$WEB_UPLOADS" ]; then
        # Copy any existing seeded uploads into persistent storage
        if [ -d "$WEB_UPLOADS" ] && [ "$(ls -A $WEB_UPLOADS 2>/dev/null)" ]; then
            cp -rn "$WEB_UPLOADS/." "$DATA_DIR/uploads/" 2>/dev/null || true
        fi
        rm -rf "$WEB_UPLOADS"
        ln -s "$DATA_DIR/uploads" "$WEB_UPLOADS"
    fi

    # Copy existing DB into persistent storage if not already there
    if [ -f "/var/www/html/tcam_bookings.db" ] && [ ! -f "$DATA_DIR/tcam_bookings.db" ]; then
        cp "/var/www/html/tcam_bookings.db" "$DATA_DIR/tcam_bookings.db"
        chown www-data:www-data "$DATA_DIR/tcam_bookings.db"
    fi

    export TCAM_DB_PATH="$DATA_DIR/tcam_bookings.db"
    export TCAM_UPLOAD_DIR="$DATA_DIR/uploads"
fi

exec "$@"
