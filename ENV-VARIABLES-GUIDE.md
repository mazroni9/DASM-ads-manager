# 🔐 دليل Environment Variables - Environment Variables Guide

## 📋 قائمة المتغيرات المطلوبة

هذه جميع Environment Variables المطلوبة لنظام إعلانات DASMe.

---

## 1. Laravel الأساسية (مطلوبة)

### APP_KEY
```
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx==
```
**الوصف**: مفتاح التشفير لـ Laravel  
**كيفية الحصول عليه**:
```bash
cd C:\dasme-ads-laravel
php artisan key:generate --show
```
**مطلوبة**: ✅ نعم (مطلوبة جداً)

### APP_ENV
```
APP_ENV=production
```
**الوصف**: بيئة التطبيق (production/development)  
**القيم**: `production` أو `local`  
**مطلوبة**: ✅ نعم

### APP_DEBUG
```
APP_DEBUG=false
```
**الوصف**: وضع التطوير (تفعيل/إيقاف عرض الأخطاء)  
**القيم**: `true` أو `false`  
**للإنتاج**: `false`  
**مطلوبة**: ✅ نعم

### APP_URL
```
APP_URL=https://dasme-ads-laravel.vercel.app
```
**الوصف**: رابط التطبيق (URL)  
**مثال**: `https://your-app.vercel.app`  
**مطلوبة**: ✅ نعم

---

## 2. قاعدة البيانات (مطلوبة)

### DB_CONNECTION
```
DB_CONNECTION=pgsql
```
**الوصف**: نوع قاعدة البيانات  
**القيم**: `mysql` أو `pgsql` (PostgreSQL)  
**مطلوبة**: ✅ نعم

### DB_HOST
```
DB_HOST=your-database-host.com
```
**الوصف**: عنوان خادم قاعدة البيانات  
**مثال**: `db.railway.app` أو `localhost`  
**مطلوبة**: ✅ نعم

### DB_PORT
```
DB_PORT=5432
```
**الوصف**: منفذ قاعدة البيانات  
**PostgreSQL**: `5432`  
**MySQL**: `3306`  
**مطلوبة**: ✅ نعم

### DB_DATABASE
```
DB_DATABASE=railway
```
**الوصف**: اسم قاعدة البيانات  
**مثال**: `railway` أو `dasme_ads`  
**مطلوبة**: ✅ نعم

### DB_USERNAME
```
DB_USERNAME=postgres
```
**الوصف**: اسم مستخدم قاعدة البيانات  
**مثال**: `postgres` أو `root`  
**مطلوبة**: ✅ نعم

### DB_PASSWORD
```
DB_PASSWORD=your-secure-password
```
**الوصف**: كلمة مرور قاعدة البيانات  
**⚠️ سرية - لا تشاركها**  
**مطلوبة**: ✅ نعم

---

## 3. DASMe Integration (مطلوبة)

### DASM_API_URL
```
DASM_API_URL=https://dasm.example.com/api/v1
```
**الوصف**: رابط API الخاص بمنصة DASMe  
**مثال**: `https://dasm.example.com/api/v1`  
**مطلوبة**: ✅ نعم (للربط مع DASMe)

### DASM_API_TOKEN
```
DASM_API_TOKEN=your-dasm-api-token-here
```
**الوصف**: Token للتوثيق مع DASMe API  
**⚠️ سرية - يجب الحصول عليها من DASMe**  
**مطلوبة**: ✅ نعم

### DASM_WEBHOOK_SECRET
```
DASM_WEBHOOK_SECRET=your-webhook-secret-key
```
**الوصف**: Secret key للتحقق من webhooks من DASMe  
**⚠️ سرية**  
**مطلوبة**: ⚠️ اختيارية (لكن مُوصى بها للأمان)

---

## 4. Ads Platform (مطلوبة)

### ADS_TRACKING_SECRET
```
ADS_TRACKING_SECRET=your-random-secret-key-here
```
**الوصف**: Secret key لتشفير tracking tokens  
**كيفية إنشائه**: أي string عشوائي طويل (مثلاً 32 حرف)  
**مثال**: `ads_tracking_secret_2024_xyz123abc456`  
**مطلوبة**: ✅ نعم

---

## 5. Session & Cache (اختيارية - لها defaults)

### SESSION_DRIVER
```
SESSION_DRIVER=file
```
**الوصف**: محرك الجلسات  
**القيم**: `file`, `redis`, `database`  
**Default**: `file`  
**مطلوبة**: ❌ لا (اختيارية)

### CACHE_DRIVER
```
CACHE_DRIVER=file
```
**الوصف**: محرك الكاش  
**القيم**: `file`, `redis`, `database`  
**Default**: `file`  
**مطلوبة**: ❌ لا (اختيارية)

---

## 6. Mail (اختيارية - للتنبيهات)

### MAIL_MAILER
```
MAIL_MAILER=smtp
```
**الوصف**: نظام البريد الإلكتروني  
**مطلوبة**: ❌ لا (إذا لم تستخدم Mail)

### MAIL_HOST, MAIL_PORT, etc.
**مطلوبة**: ❌ لا (فقط إذا استخدمت Mail)

---

## 📝 قائمة كاملة للنسخ واللصق

### للـ Vercel/Railway (Production)

```env
# Laravel
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx==
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host.com
DB_PORT=5432
DB_DATABASE=your-database-name
DB_USERNAME=your-db-username
DB_PASSWORD=your-db-password

# DASMe Integration
DASM_API_URL=https://dasm.example.com/api/v1
DASM_API_TOKEN=your-dasm-api-token
DASM_WEBHOOK_SECRET=your-webhook-secret

# Ads Platform
ADS_TRACKING_SECRET=your-random-secret-key-32-chars-long
```

---

## 🔧 كيفية الحصول على القيم

### 1. APP_KEY
```bash
cd C:\dasme-ads-laravel
php artisan key:generate --show
```
انسخ النتيجة وأضفها في `APP_KEY`.

### 2. Database (إذا استخدمت Railway Postgres)
Railway يعطيك هذه القيم تلقائياً في Dashboard:
- `DB_HOST` = من Railway Dashboard
- `DB_DATABASE` = من Railway Dashboard
- `DB_USERNAME` = من Railway Dashboard
- `DB_PASSWORD` = من Railway Dashboard

### 3. DASM_API_TOKEN
- يجب الحصول عليه من فريق DASMe
- أو من إعدادات DASMe Platform

### 4. ADS_TRACKING_SECRET
أنشئ أي string عشوائي طويل:
```bash
# أو استخدم online generator
# مثال: ads_secret_2024_xyz123abc456def789
```

---

## 📋 خطوات الإضافة في Vercel

1. اذهب إلى: https://vercel.com/dasme-projects/dasme-ads-laravel/settings
2. اضغط **"Environment Variables"**
3. أضف كل متغير:
   - **Key**: `APP_KEY`
   - **Value**: `base64:...`
   - **Environment**: `Production`, `Preview`, `Development`
4. اضغط **"Save"**
5. كرر لكل متغير

---

## 📋 خطوات الإضافة في Railway

1. اذهب إلى Railway Dashboard → Project → Variables
2. اضغط **"New Variable"**
3. أضف Key و Value
4. Railway يطبّقها على جميع Environments تلقائياً

---

## ⚠️ ملاحظات مهمة

1. **لا تضع `.env` في Git** - الملف محمي في `.gitignore`
2. **APP_KEY** - مهم جداً، بدونها Laravel لا يعمل
3. **Database** - يجب إنشاء Database أولاً
4. **DASM_API_TOKEN** - يجب الحصول عليه من DASMe Platform
5. **ADS_TRACKING_SECRET** - أي string عشوائي طويل

---

## ✅ Checklist

- [ ] APP_KEY (مطلوب - generate it)
- [ ] APP_ENV = production
- [ ] APP_DEBUG = false
- [ ] APP_URL = production URL
- [ ] DB_CONNECTION = pgsql
- [ ] DB_HOST (من Database provider)
- [ ] DB_PORT = 5432
- [ ] DB_DATABASE (من Database provider)
- [ ] DB_USERNAME (من Database provider)
- [ ] DB_PASSWORD (من Database provider)
- [ ] DASM_API_URL (رابط DASMe API)
- [ ] DASM_API_TOKEN (من DASMe)
- [ ] ADS_TRACKING_SECRET (أنشئه بنفسك)

---

**آخر تحديث**: 2024-01-18
