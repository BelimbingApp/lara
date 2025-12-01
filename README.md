# BLB - Laravel Livewire Application

A modern full-stack web application built with Laravel 12, Livewire, and Flux UI components.

## 🚀 Technology Stack

### Backend
- **Laravel 12** - Latest PHP framework with modern features
- **PHP 8.2+** - Modern PHP with enhanced performance
- **Livewire Volt** - Reactive components with minimal boilerplate
- **Livewire Flux** - Professional UI component library
- **PostgreSQL** - Robust relational database

### Frontend
- **TailwindCSS 4.0** - Utility-first CSS framework
- **Vite** - Fast build tool and development server
- **Alpine.js** - Lightweight JavaScript framework (via Livewire)
- **Responsive Design** - Mobile-first approach with dark/light mode

### Development Tools
- **Pest** - Modern PHP testing framework
- **Laravel Pail** - Real-time log monitoring
- **Laravel Pint** - Code style fixer
- **Laravel Sail** - Docker development environment

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & npm
- PostgreSQL
- Git

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url> blb
   cd blb
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database configuration**
   
   Update your `.env` file with PostgreSQL credentials:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=blb
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Start development servers**
   ```bash
   composer run dev
   ```

## 🎯 Features

### Authentication System
- User registration and login
- Password reset functionality
- Email verification
- Rate limiting for security
- Remember me functionality

### User Interface
- **Welcome Page** - Laravel branding with getting started guide
- **Dashboard** - Main application workspace
- **Settings Pages**:
  - Profile management
  - Password changes
  - Appearance preferences (dark/light mode)

### Technical Features
- Full-stack reactive components with Livewire
- Modern UI with Flux components
- Responsive design
- Database sessions and queues
- Real-time logging
- Comprehensive testing setup

## 🛠️ Development Commands

### Start Development Environment
```bash
# Starts all services: server, queue, logs, and Vite
composer run dev
```

### Individual Commands
```bash
# Start Laravel server
php artisan serve

# Start Vite development server
npm run dev

# Watch logs in real-time
php artisan pail

# Process queue jobs
php artisan queue:work
```

### Testing
```bash
# Run all tests
composer run test
# or
php artisan test
```

### Code Quality
```bash
# Fix code style
./vendor/bin/pint

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## 📁 Project Structure

```
blb/
├── app/
│   ├── Http/Controllers/     # HTTP Controllers
│   ├── Models/              # Eloquent Models
│   ├── Livewire/            # Livewire Components
│   └── Providers/           # Service Providers
├── resources/
│   ├── views/               # Blade Templates
│   │   └── livewire/        # Livewire Views
│   ├── js/                  # JavaScript Assets
│   └── css/                 # CSS Assets
├── routes/
│   ├── web.php              # Web Routes
│   ├── auth.php             # Authentication Routes
│   └── console.php          # Console Routes
├── database/
│   ├── migrations/          # Database Migrations
│   ├── factories/           # Model Factories
│   └── seeders/             # Database Seeders
└── tests/                   # Test Files
```

## 🌐 Routes

- `/` - Welcome page
- `/login` - User login
- `/register` - User registration
- `/dashboard` - Main dashboard (authenticated)
- `/settings/profile` - Profile management
- `/settings/password` - Password change
- `/settings/appearance` - Theme preferences

## 🔐 Environment Variables

Key environment variables to configure:

```env
APP_NAME=BLB
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blb
DB_USERNAME=postgres
DB_PASSWORD=your_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

## 🧪 Testing

The application uses Pest for testing with Laravel-specific plugins:

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

## 📦 Deployment

1. **Production Environment**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```

2. **Database Setup**
   ```bash
   php artisan migrate --force
   ```

3. **Queue Workers** (if using queues in production)
   ```bash
   php artisan queue:work --daemon
   ```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Use conventional commit messages
- Run `./vendor/bin/pint` before committing

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

- **Documentation**: [Laravel Documentation](https://laravel.com/docs)
- **Livewire**: [Livewire Documentation](https://livewire.laravel.com)
- **Flux UI**: [Flux Documentation](https://fluxui.dev)

## 🔄 Version

- **Laravel**: ^12.0
- **PHP**: ^8.2
- **Livewire**: Latest
- **TailwindCSS**: ^4.0

---

Built with ❤️ using Laravel and Livewire