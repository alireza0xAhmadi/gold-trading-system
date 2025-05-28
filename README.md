<div dir="rtl" style="font-family: Tahoma,serif;">

# 🥇 سیستم معاملات طلا

یک سیستم معاملات طلای آنلاین مبتنی بر Laravel با قابلیت ثبت سفارشات خرید/فروش، تطبیق خودکار و مدیریت کارمزد.

## ✨ ویژگی‌های اصلی

- 📈 **ثبت سفارشات خرید و فروش** طلا
- 🔄 **تطبیق خودکار سفارشات** بر اساس قیمت و زمان
- 💰 **محاسبه کارمزد هوشمند** با نرخ‌های متغیر
- 📊 **مدیریت موجودی** طلا و ریال
- 📋 **تاریخچه معاملات** کامل
- ❌ **لغو سفارشات** با بازگشت موجودی
- 🧪 **تست‌های جامع** برای تمام سناریوها

## 🛠 تکنولوژی‌های استفاده شده

- **Backend:** Laravel 10.x + Laravel Octane (Swoole)
- **Database:** MySQL 8.0
- **Cache:** Redis 7
- **Web Server:** Nginx
- **Containerization:** Docker & Docker Compose
- **Testing:** PHPUnit
- **Architecture:** Repository Pattern + Service Layer
- **API:** RESTful API

## 📦 نصب و راه‌اندازی

### پیش‌نیازها
- Docker & Docker Compose
- Git

**یا برای نصب محلی:**
- PHP >= 8.2
- Composer
- MySQL
- Redis

### 🐳 نصب با Docker (پیشنهادی)

<div dir="ltr" style="font-family: 'Courier New', monospace;">

```bash
# Clone the project
git clone https://github.com/alireza0xAhmadi/gold-trading-system
cd gold-trading-system

# Copy environment file
cp .env.example .env

# Build and start containers
docker-compose up -d --build

# Install dependencies inside container
docker-compose exec app composer install

# Generate App Key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Run seeders (optional)
docker-compose exec app php artisan db:seed
```

**دسترسی به سرویس‌ها:**
- 🌐 Application: http://localhost (Nginx → Octane)
- 📊 phpMyAdmin: http://localhost:8080
- 🗄️ MySQL: localhost:3306
- 📦 Redis: localhost:6379
- ⚡ Octane Direct: http://localhost:8000

</div>

### 🔧 نصب محلی

<div dir="ltr">

```bash
# Clone the project
git clone https://github.com/alireza0xAhmadi/gold-trading-system
cd gold-trading-system

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate App Key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gold_trading
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Run seeders (optional)
php artisan db:seed

# Start the server
php artisan serve
```

</div>

## 🧪 اجرای تست‌ها

<div dir="ltr">

```bash
# With Docker
docker-compose exec app php artisan test

# With Docker - Run specific tests
docker-compose exec app php artisan test tests/Feature/TradingScenarioTest.php

# With Docker - Run tests with detailed output
docker-compose exec app php artisan test --verbose

# Local installation
php artisan test

# Local - Run specific tests
php artisan test tests/Feature/TradingScenarioTest.php

# Local - Run tests with detailed output
php artisan test --verbose
```

</div>

## 📚 مستندات API

### آدرس پایه

```
# با Docker (از طریق Nginx)
http://localhost/api/v1

# دسترسی مستقیم به Octane
http://localhost:8000/api/v1

# نصب محلی
http://localhost:8000/api/v1
```

### نقاط انتهایی (Endpoints)

#### 🛒 ثبت سفارش خرید

```http
POST /orders/buy
Content-Type: application/json

{
    "user_id": 1,
    "quantity": 2.5,
    "price_per_gram": 100000000
}
```

#### 🏷 ثبت سفارش فروش

```http
POST /orders/sell
Content-Type: application/json

{
    "user_id": 2,
    "quantity": 1.0,
    "price_per_gram": 100000000
}
```

#### 📋 مشاهده سفارشات فعال

```http
GET /orders/active/buy    # سفارشات خرید
GET /orders/active/sell   # سفارشات فروش
```

#### ❌ لغو سفارش

```http
PATCH /orders/{orderId}/cancel
Content-Type: application/json

{
    "user_id": 1
}
```

#### 📊 تاریخچه معاملات کاربر

```http
GET /transactions/user/{userId}
```

## 💼 منطق کسب‌وکار

### نحوه تطبیق سفارشات
1. **اولویت قیمت:** سفارشات با قیمت بهتر اولویت دارند
2. **اولویت زمانی:** در قیمت یکسان، سفارش قدیمی‌تر اولویت دارد
3. **تطبیق جزئی:** اگر مقدار کامل موجود نباشد، بخشی تطبیق می‌شود

### نرخ کارمزد

| مقدار طلا | نرخ کارمزد |
|-----------|------------|
| تا ۱ گرم | ۲٪ |
| ۱ تا ۱۰ گرم | ۱.۵٪ |
| بالای ۱۰ گرم | ۱٪ |

**محدودیت‌ها:**
- حداقل کارمزد: ۵۰۰,۰۰۰ ریال (۵۰ هزار تومان)
- حداکثر کارمزد: ۵۰,۰۰۰,۰۰۰ ریال (۵ میلیون تومان)

### مدیریت موجودی
- **سفارش خرید:** ریال بلافاصله کسر می‌شود (رزرو)
- **سفارش فروش:** طلا بلافاصله کسر می‌شود (رزرو)
- **تطبیق:** انتقال واقعی دارایی‌ها انجام می‌شود
- **لغو:** موجودی رزرو شده برگردانده می‌شود

## 🏗 ساختار پروژه

<div dir="ltr">

```
app/
├── Http/Controllers/Api/
│   ├── OrderController.php          # Order Controller
│   └── TransactionController.php    # Transaction Controller
├── Models/
│   ├── User.php                     # User Model
│   ├── Order.php                    # Order Model
│   └── Transaction.php              # Transaction Model
├── Repositories/
│   ├── Interfaces/                  # Repository Interfaces
│   ├── OrderRepository.php          # Order Repository
│   └── TransactionRepository.php    # Transaction Repository
├── Services/
│   ├── TradingService.php           # Main Trading Service
│   └── CommissionService.php        # Commission Calculation Service
└── Providers/
    └── RepositoryServiceProvider.php # Dependency Injection

tests/Feature/
├── TradingScenarioTest.php          # Main Trading Scenarios Test
└── TradingEdgeCasesTest.php         # Edge Cases Test
```

</div>

## 📊 مثال سناریو معاملاتی

```
👤 احمد: می‌خواهد ۲ گرم طلا بخرد (قیمت: ۱۰۰ میلیون ریال/گرم)
👤 رضا: می‌خواهد ۵ گرم طلا بخرد (قیمت: ۱۰۰ میلیون ریال/گرم)  
👤 اکبر: ۱۰ گرم طلا می‌فروشد (قیمت: ۱۰۰ میلیون ریال/گرم)

🔄 نتیجه تطبیق خودکار:
├─ احمد: ۲ گرم طلا دریافت کرد
├─ رضا: ۵ گرم طلا دریافت کرد
└─ اکبر: ۷ گرم فروخت، ۳ گرم باقی‌مانده

💰 کارمزدها:
├─ احمد: ۳ میلیون ریال (۱.۵٪ از ۲۰۰ میلیون)
└─ رضا: ۷.۵ میلیون ریال (۱.۵٪ از ۵۰۰ میلیون)
```

## 🧪 تست‌های موجود

### TradingScenarioTest
- ✅ Complete trading scenario
- ✅ Correct commission calculation
- ✅ Transaction history
- ✅ Partial order completion

### TradingEdgeCasesTest
- ✅ Sequential order execution
- ✅ Complex multi-stage scenarios
- ✅ Commission calculation precision
- ✅ Special case handling

## 🚀 ویژگی‌های آتی

- [ ] اعلان‌های لحظه‌ای (Real-time)
- [ ] نمودار قیمت طلا
- [ ] درگاه API
- [ ] کش برای عملکرد بهتر
- [ ] داشبورد مدیریتی
- [ ] گزارش‌گیری پیشرفته

## 🤝 مشارکت

۱. Fork کنید  
۲. شاخه جدید بسازید (`git checkout -b feature/amazing-feature`)  
۳. تغییرات را commit کنید (`git commit -m '✨ Add amazing feature'`)  
۴. شاخه را push کنید (`git push origin feature/amazing-feature`)  
۵. Pull Request ایجاد کنید

## 📄 مجوز

این پروژه تحت مجوز MIT منتشر شده است. برای جزئیات بیشتر فایل [LICENSE](LICENSE) را مطالعه کنید.

## 👨‍💻 توسعه‌دهنده

**علیرضا احمدی**
- GitHub: [@alireza0xAhmadi](https://github.com/alireza0xAhmadi)
- Email: blackhat1520@gmail.com

---

⭐ اگر این پروژه برایتان مفید بود، حتماً ستاره بدهید!

</div>
