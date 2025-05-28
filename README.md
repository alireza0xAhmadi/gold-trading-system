<div dir="rtl">

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

- **Backend:** Laravel 10.x
- **Database:** MySQL/SQLite
- **Testing:** PHPUnit
- **Architecture:** Repository Pattern + Service Layer
- **API:** RESTful API

## 📦 نصب و راه‌اندازی

### پیش‌نیازها
- PHP >= 8.1
- Composer
- MySQL/SQLite
- Git

### مراحل نصب

```bash
# کلون کردن پروژه
git clone https://github.com/alireza0xAhmadi/gold-trading-system
cd gold-trading-system

# نصب dependencies
composer install

# کپی فایل environment
cp .env.example .env

# تولید App Key
php artisan key:generate

# تنظیم دیتابیس در .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gold_trading
DB_USERNAME=your_username
DB_PASSWORD=your_password

# اجرای migrations
php artisan migrate

# اجرای seeders (اختیاری)
php artisan db:seed

# شروع سرور
php artisan serve
```

## 🧪 اجرای تست‌ها

```bash
# اجرای تمام تست‌ها
php artisan test

# اجرای تست‌های خاص
php artisan test tests/Feature/TradingScenarioTest.php

# اجرای تست‌ها با جزئیات
php artisan test --verbose
```

## 📚 مستندات API

### آدرس پایه

```
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

```
app/
├── Http/Controllers/Api/
│   ├── OrderController.php          # کنترلر سفارشات
│   └── TransactionController.php    # کنترلر معاملات
├── Models/
│   ├── User.php                     # مدل کاربر
│   ├── Order.php                    # مدل سفارش
│   └── Transaction.php              # مدل معامله
├── Repositories/
│   ├── Interfaces/                  # اینترفیس‌های Repository
│   ├── OrderRepository.php          # Repository سفارشات
│   └── TransactionRepository.php    # Repository معاملات
├── Services/
│   ├── TradingService.php           # سرویس اصلی معاملات
│   └── CommissionService.php        # سرویس محاسبه کارمزد
└── Providers/
    └── RepositoryServiceProvider.php # تزریق وابستگی

tests/Feature/
├── TradingScenarioTest.php          # تست سناریوهای اصلی
└── TradingEdgeCasesTest.php         # تست حالات خاص
```

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
- ✅ سناریو کامل معاملاتی
- ✅ محاسبه صحیح کارمزد
- ✅ تاریخچه معاملات
- ✅ تکمیل جزئی سفارش

### TradingEdgeCasesTest
- ✅ اجرای ترتیبی سفارشات
- ✅ سناریوهای پیچیده چند مرحله‌ای
- ✅ دقت محاسبه کارمزد
- ✅ مدیریت حالات خاص

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

**نام شما**
- GitHub: [@your-username](https://github.com/your-username)
- Email: your-email@example.com

---

⭐ اگر این پروژه برایتان مفید بود، حتماً ستاره بدهید!

</div>
