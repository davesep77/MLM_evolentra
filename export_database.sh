#!/bin/bash

# ============================================
# Database Schema Export Script
# ============================================
# This script exports your local database schema
# for deployment to DigitalOcean
#
# Usage: ./export_database.sh
# ============================================

echo "🗄️  Evolentra Database Export Tool"
echo "===================================="
echo ""

# Configuration
DB_NAME="evolentra"
OUTPUT_FILE="database/schema.sql"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="database/backup_${TIMESTAMP}.sql"

# Create database directory if it doesn't exist
mkdir -p database

echo "📋 Exporting database schema..."
echo ""

# Check if mysqldump is available
if ! command -v mysqldump &> /dev/null; then
    echo "❌ Error: mysqldump not found"
    echo "Please install MySQL client tools"
    exit 1
fi

# Prompt for MySQL credentials
echo "Enter MySQL credentials:"
read -p "Host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Username [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Password: " DB_PASS
echo ""
echo ""

# Export schema and data
echo "Exporting to: $OUTPUT_FILE"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
  --databases "$DB_NAME" \
  --add-drop-database \
  --add-drop-table \
  --routines \
  --triggers \
  --events \
  > "$OUTPUT_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Export successful!"
    echo ""
    echo "📄 Schema exported to: $OUTPUT_FILE"
    
    # Also create a backup
    cp "$OUTPUT_FILE" "$BACKUP_FILE"
    echo "💾 Backup created: $BACKUP_FILE"
    echo ""
    
    # Show file size
    SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "📊 File size: $SIZE"
    echo ""
    
    echo "🚀 Next steps:"
    echo "1. Review the exported schema: $OUTPUT_FILE"
    echo "2. Import to DigitalOcean database (see DEPLOYMENT_GUIDE.md)"
    echo "3. Verify all tables are present"
    echo ""
else
    echo "❌ Export failed!"
    echo "Please check your credentials and try again"
    exit 1
fi
