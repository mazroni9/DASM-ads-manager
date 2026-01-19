# 🔄 إعداد النشر التلقائي من GitHub إلى Vercel

## ✅ الربط التلقائي جاهز!

بعد ربط المشروع مع Vercel لأول مرة، كل **push** إلى GitHub سيتم نشره تلقائياً على Vercel.

## 🚀 طريقة الربط (مرة واحدة فقط)

### الطريقة 1: من Vercel Dashboard (الأسهل)

1. اذهب إلى [vercel.com](https://vercel.com)
2. اضغط **"Add New Project"**
3. اختر **"Import Git Repository"**
4. اختر `mazroni9/DASM-ads-manager`
5. في إعدادات المشروع:
   - ✅ تأكد من **"Automatically deploy"** مفعّل
   - ✅ Framework Preset: **Laravel**
6. أضف Environment Variables (انظر أدناه)
7. اضغط **"Deploy"**

### الطريقة 2: Vercel CLI

```bash
# Login
vercel login

# Link project
cd C:\dasme-ads-laravel
vercel link

# Deploy
vercel --prod
```

## ⚙️ إعدادات GitHub Integration

Vercel سيقوم تلقائياً بـ:
- ✅ اكتشاف كل `git push` جديد
- ✅ تشغيل Build تلقائياً
- ✅ Deploy النسخة الجديدة
- ✅ إرسال إشعارات (إذا فعّلت)

## 🔧 Environment Variables

في Vercel Dashboard → Settings → Environment Variables:

```env
APP_KEY=base64:YOUR_KEY
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

DB_CONNECTION=pgsql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

DASM_API_URL=...
DASM_API_TOKEN=...
ADS_TRACKING_SECRET=...
```

## 📋 بعد الربط

بمجرد ربط المشروع:

1. **كل commit + push** → Vercel يبني وينشر تلقائياً
2. **Pull Requests** → Vercel ينشئ Preview deployments
3. **Production** → كل push إلى `main` branch ينشر مباشرة

## 🔍 التحقق

بعد الربط، ستجد في GitHub:
- ✅ **Deployment badge** في README (اختياري)
- ✅ **Deployments tab** في Vercel Dashboard
- ✅ **Webhooks** في GitHub Settings → Webhooks

---

**الربط التلقائي يعمل فور ربط المشروع مع Vercel لأول مرة!**
