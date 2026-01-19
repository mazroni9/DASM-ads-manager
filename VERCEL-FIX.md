# 🔧 إصلاح Vercel - Vercel Fix

## ⚠️ المشكلة

**Error**: `composer: command not found`

Vercel يحاول تشغيل `composer install` لكن Composer غير مثبت في بيئة Vercel.

---

## ✅ الحل: إزالة installCommand من Vercel Settings

### الخطوات:

1. **اذهب إلى Vercel Dashboard**:
   https://vercel.com/dasme-projects/dasme-ads-laravel/settings

2. **General Settings** → **Build & Development Settings**

3. **احذف أو اترك فارغ**:
   - ❌ **Install Command**: اتركه فارغ (لا تضع `composer install`)
   - ✅ **Build Command**: اتركه فارغ أيضاً
   - ✅ **Output Directory**: `public`
   - ✅ **Root Directory**: `.` (root)

4. **حفظ الإعدادات**

**السبب**: `vercel-php` runtime يتعامل مع Composer تلقائياً - لا تحتاج `composer install` في Build Command.

---

## 🔄 تحديث vercel.json

تم تحديث `vercel.json` لاستخدام `vercel-php` runtime بشكل صحيح.

**ملف `vercel.json` الجديد**:
```json
{
  "version": 2,
  "functions": {
    "api/**/*.php": {
      "runtime": "vercel-php@0.7.4"
    }
  },
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/public/index.php"
    }
  ]
}
```

---

## 🚀 بعد الإصلاح

1. ✅ تحديث `vercel.json` (تم ✅)
2. ⏳ إزالة `installCommand` من Vercel Settings (افعلها الآن)
3. ⏳ Push التحديثات إلى GitHub (سيتم Deploy تلقائياً)

```bash
git add vercel.json
git commit -m "Fix vercel.json for PHP runtime"
git push
```

---

## ⚠️ إذا لم يعمل: استخدم Railway

**Vercel محدود للـ PHP/Laravel**. إذا استمرت المشكلة:

### Railway.app (مُوصى به للـ Laravel) ⭐

1. اذهب إلى [railway.app](https://railway.app)
2. Login with GitHub
3. New Project → Deploy from GitHub repo
4. اختر `mazroni9/DASM-ads-manager`
5. Railway يكتشف Laravel تلقائياً ✅
6. Add PostgreSQL Database
7. Deploy! ✅

**Railway يدعم PHP/Composer بشكل كامل!**

---

## 📊 المقارنة

| Platform | PHP Support | Composer | Deploy | السعر |
|----------|-------------|----------|--------|-------|
| **Railway** | ✅ كامل | ✅ نعم | ✅ سهل | مجاني |
| **Vercel** | ⚠️ محدود | ⚠️ معقد | ⚠️ يحتاج config | مجاني |

---

## ✅ Checklist

- [x] تحديث `vercel.json`
- [ ] إزالة `installCommand` من Vercel Settings
- [ ] Push التحديثات
- [ ] اختبار Deploy

**إذا لم يعمل**: استخدم Railway ⭐
