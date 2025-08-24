# Giày dép Hồng An - Laravel 12 + Nuxt 4

Full-stack application sử dụng Laravel 12 làm backend API và Nuxt 4 với TypeScript làm frontend.

## 🚀 Cấu trúc dự án

```
giaydephongan/
├── app/                    # Laravel application logic
│   └── Http/Controllers/Api/  # API Controllers
├── config/                 # Laravel configuration
├── database/               # Database migrations & seeders
├── frontend/               # Nuxt 4 application
│   ├── app.vue            # Main page
│   └── nuxt.config.ts     # Nuxt configuration
├── routes/                 # Laravel routes
├── storage/                # Laravel storage
└── vendor/                 # Composer dependencies
```

## 📋 Yêu cầu hệ thống

- PHP 8.1+
- Node.js 18+
- Composer
- MySQL/PostgreSQL

## 🛠️ Cài đặt

### 1. Clone repository
```bash
git clone <repository-url>
cd giaydephongan
```

### 2. Setup dự án
```bash
# Cài đặt tất cả dependencies
npm run setup

# Hoặc cài đặt từng phần
composer install
cd frontend && npm install
```

### 3. Cấu hình môi trường
```bash
# Copy file môi trường
cp .env.example .env

# Tạo application key
php artisan key:generate

# Cấu hình database trong .env
```

### 4. Chạy migrations
```bash
php artisan migrate
```

## 🚀 Chạy dự án

### Development mode
```bash
# Chạy cả Laravel và Nuxt cùng lúc
npm run dev

# Hoặc chạy riêng lẻ
npm run dev:laravel    # Laravel server (http://localhost:8000)
npm run dev:nuxt       # Nuxt dev server (http://localhost:3000)
```

### Production build
```bash
npm run build
```

## 📁 Cấu trúc API

### Laravel Backend
- **URL**: `http://localhost:8000`
- **API Base**: `http://localhost:8000/api`
- **Health Check**: `http://localhost:8000/api/health`

### Nuxt Frontend
- **URL**: `http://localhost:3000`
- **API Integration**: Tự động kết nối với Laravel API

## 🛠️ Development

### Laravel Commands
```bash
php artisan make:controller Api/UserController
php artisan make:model User -m
php artisan make:resource UserResource
```

### Nuxt Commands
```bash
cd frontend
npx nuxi add page about
npx nuxi add component UserCard
```

## 📦 Scripts có sẵn

- `npm run dev` - Chạy cả Laravel và Nuxt
- `npm run build` - Build Nuxt cho production
- `npm run install:all` - Cài đặt tất cả dependencies
- `npm run setup` - Setup hoàn chỉnh dự án

## 🔧 Cấu hình

### Laravel (.env)
```env
APP_NAME="Giay De Phong An"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=giaydephongan
DB_USERNAME=root
DB_PASSWORD=
```

### Nuxt (frontend/.env)
```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
```

## 📚 Tài liệu tham khảo

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Nuxt 4 Documentation](https://nuxt.com/docs)
- [TypeScript Documentation](https://www.typescriptlang.org/docs)

## 🤝 Đóng góp

1. Fork dự án
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📄 License

MIT License - xem file [LICENSE](LICENSE) để biết thêm chi tiết.
