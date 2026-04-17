# DASMe Ads Platform - نظام إعلانات DASMe

## 🎉 Laravel Project كامل - جاهز للنشر!

هذا **Laravel 12 project كامل** يحتوي على نظام إعلانات متكامل لمنصة DASMe.

## ✨ المميزات

- ✅ **Backend API كامل** - جميع Endpoints جاهزة
- ✅ **7 Services** - Ranking, Serving, Tracking, Billing, Anti-Fraud
- ✅ **6 Controllers** - Campaign, Creative, Account, Wallet, Reports
- ✅ **8 Models** - مع العلاقات الكاملة
- ✅ **8 Migrations** - قاعدة البيانات جاهزة
- ✅ **DASMe Integration** - ربط مع منصة DASMe الأم

## 🚀 النشر على Vercel

### الخطوة 1: إعداد Environment Variables

في Vercel Dashboard → Settings → Environment Variables:

```env
APP_KEY=base64:... (generate with: php artisan key:generate)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

DASM_API_URL=https://dasm.example.com/api/v1
DASM_API_TOKEN=your-token
ADS_TRACKING_SECRET=your-secret-key
```

### الخطوة 2: Deploy على Vercel

1. اذهب إلى [vercel.com](https://vercel.com)
2. اضغط "Add New Project"
3. Import من GitHub: `DASMe-9/dasm-ads`
4. Vercel سيكتشف Laravel تلقائياً
5. اضغط "Deploy"

### الخطوة 3: إعداد Database

بعد النشر، قم بتشغيل Migrations:

```bash
# في Vercel CLI أو SSH
php artisan migrate
```

## 📋 API Endpoints

### Public (لا يحتاج auth)
- `GET /api/ads/serve` - عرض الإعلانات
- `POST /api/ads/track` - تتبع الأحداث

### Protected (يحتاج Bearer token)
- `GET /api/ads/account` - الحساب
- `GET /api/ads/campaigns` - قائمة الحملات
- `POST /api/ads/campaigns` - إنشاء حملة
- `GET /api/ads/wallet/transactions` - سجل المعاملات
- `POST /api/ads/wallet/topup` - شحن الرصيد
- `GET /api/ads/reports/summary` - التقارير

## 🔧 التطوير المحلي

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start server
php artisan serve
```

## 📚 الوثائق

جميع الوثائق في مجلد `docs/`:
- ERD.md - مخطط قاعدة البيانات
- API.md - مواصفات API
- DASM-INTEGRATION.md - دليل Integration

## 🔗 Links

- **GitHub**: https://github.com/DASMe-9/dasm-ads
- **Vercel**: (بعد النشر)

---

**تم البناء**: 2024-01-18  
**Laravel Version**: 12.11.1  
**PHP Version**: 8.4+
