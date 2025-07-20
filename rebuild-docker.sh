#!/bin/bash

echo "🚀 Rebuilding Docker containers with performance optimizations..."

# Stop existing containers
echo "📦 Stopping existing containers..."
docker-compose down

# Remove old images to ensure clean rebuild
echo "🧹 Cleaning old images..."
docker-compose down --rmi all --volumes --remove-orphans

# Build with no cache to ensure all optimizations are applied
echo "🔨 Building containers with optimizations..."
docker-compose build --no-cache

# Start containers
echo "🚀 Starting optimized containers..."
docker-compose up -d

# Wait for containers to be ready
echo "⏳ Waiting for containers to be ready..."
sleep 10

# Run Laravel optimizations
echo "⚡ Running Laravel optimizations..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app composer install --optimize-autoloader --no-dev

# Set proper permissions
echo "🔐 Setting proper permissions..."
docker-compose exec app chown -R www:www /var/www/storage
docker-compose exec app chown -R www:www /var/www/bootstrap/cache
docker-compose exec app chmod -R 755 /var/www/storage
docker-compose exec app chmod -R 755 /var/www/bootstrap/cache

echo "✅ Docker containers rebuilt and optimized!"
echo "🌐 Your application is now running at: http://localhost:8081"
echo ""
echo "📊 Performance improvements applied:"
echo "   • OPcache enabled with 128MB memory"
echo "   • Redis caching added"
echo "   • Nginx static file caching"
echo "   • Gzip compression enabled"
echo "   • Optimized volume mounting"
echo "   • FastCGI buffer optimizations"
echo ""
echo "🔧 To check container status: docker-compose ps"
echo "📝 To view logs: docker-compose logs -f" 