#!/bin/bash
set -e

echo "🚀 Starting Challenge QA Application..."

# Create logs directory if not exists
mkdir -p /var/www/html/logs

# Set proper permissions
chown -R www-data:www-data /var/www/html/logs
chmod -R 755 /var/www/html/logs

# Wait for MySQL to be ready
echo "Waiting for MySQL connection..."
until nc -z mysql 3306; do
  echo "MySQL not ready, waiting..."
  sleep 2
done
echo "MySQL is ready!"

# Run database migrations if needed
echo "Running database setup..."
if [ -f "/var/www/html/migrations-db.php" ]; then
    php /var/www/html/migrations-db.php
fi

# Set environment variables for Apache
export APACHE_DOCUMENT_ROOT=/var/www/html/public

echo "🎯 Application ready! Access at http://localhost:8080"
echo "📊 Available endpoints:"
echo "  - POST /api/users/register - User registration"
echo "  - POST /api/users/login - User authentication" 
echo "  - POST /api/calculations/simple - Simple interest calculation"
echo "  - POST /api/calculations/compound - Compound interest calculation"
echo "  - POST /api/calculations/installment - Installment simulation"

echo "Starting Apache server..."
exec "$@"