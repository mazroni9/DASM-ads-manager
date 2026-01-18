# 🚀 تعليمات النشر على Vercel - Vercel Deployment Instructions

## ✅ تم رفع Laravel Project إلى GitHub!

**Repository**: https://github.com/mazroni9/DASM-ads-manager

## 📋 خطوات النشر على Vercel

### 1. إنشاء حساب Vercel (إذا لم يكن موجود)

اذهب إلى [vercel.com](https://vercel.com) وسجّل دخول بحساب GitHub.

### 2. Import Project

1. اضغط **"Add New Project"**
2. اختر **"Import Git Repository"**
3. ابحث عن `mazroni9/DASM-ads-manager`
4. اضغط **"Import"**

### 3. إعدادات Build

Vercel سيكتشف Laravel تلقائياً، لكن تأكد من:

**Framework Preset**: Laravel  
**Build Command**: `composer install --no-dev --optimize-autoloader`  
**Output Directory**: `public`  
**Install Command**: `composer install`

### 4. Environment Variables

في **Settings → Environment Variables**، أضف:

```env
# Laravel
APP_KEY=base64:YOUR_GENERATED_KEY
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

# Database (استخدم Vercel Postgres أو external DB)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

# DASMe Integration
DASM_API_URL=https://dasm.example.com/api/v1
DASM_API_TOKEN=your-token-here
DASM_WEBHOOK_SECRET=webhook-secret

# Ads Platform
ADS_TRACKING_SECRET=your-secret-key-here
```

**ملاحظة**: لإنشاء `APP_KEY`:
```bash
php artisan key:generate --show
```

### 5. Database Setup

#### خيار 1: Vercel Postgres (مُوصى به)

1. في Vercel Dashboard → Storage → Create Database
2. اختر **Postgres**
3. Vercel سيعطيك connection string تلقائياً

#### خيار 2: External Database

استخدم:
- **PlanetScale** (MySQL)
- **Supabase** (PostgreSQL)
- **Railway** (PostgreSQL)

### 6. Run Migrations

بعد النشر الأول، قم بتشغيل Migrations:

**خيار 1: Vercel CLI**
```bash
vercel login
vercel link
vercel env pull .env.local
php artisan migrate --force
```

**خيار 2: SSH (إذا متاح)**
```bash
ssh your-vercel-instance
cd /var/www
php artisan migrate --force
```

**خيار 3: Vercel Functions (Automated)**

أنشئ `api/migrate.php`:
```php
<?php
// This will run migrations on each deploy
// (Not recommended for production, use manual)
```

### 7. Deploy!

اضغط **"Deploy"** وانتظر حتى يكتمل البناء.

## 🔍 التحقق من النشر

بعد النشر:

1. **Health Check**: `https://your-app.vercel.app/up`
2. **API Test**: `https://your-app.vercel.app/api/ads/serve?placement=search_listings&session_id=test`

## ⚠️ ملاحظات مهمة

### 1. Database Migrations

Vercel لا يدعم `php artisan migrate` تلقائياً. يجب:
- تشغيل Migrations يدوياً بعد النشر الأول
- أو استخدام Vercel Postgres + Migration script

### 2. Storage

Laravel يحتاج `storage/` directory للـ logs و cache. تأكد من:
- `storage/` موجود في repository
- Permissions صحيحة (Vercel يتعامل معها تلقائياً)

### 3. Queue Jobs

إذا استخدمت Queue Jobs، ستحتاج:
- Redis (Vercel KV أو Upstash)
- أو Queue driver = `sync` (للـ MVP)

### 4. Caching

استخدم Vercel KV أو Redis external للـ cache.

## 🎯 بعد النشر

### Test APIs

```bash
# Test serve endpoint
curl https://your-app.vercel.app/api/ads/serve?placement=search_listings&session_id=test123

# Test with auth (needs token)
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://your-app.vercel.app/api/ads/campaigns
```

### Monitor

- Vercel Dashboard → Analytics
- Vercel Dashboard → Logs

## 📞 الدعم

إذا واجهت مشاكل:
1. تحقق من Vercel Logs
2. تحقق من Environment Variables
3. تأكد من Database connection
4. تحقق من Laravel logs في `storage/logs/`

---

**آخر تحديث**: 2024-01-18
