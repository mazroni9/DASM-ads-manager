# 🚂 النشر على Railway - Deploy on Railway

## ✅ Railway هو الأفضل للـ Laravel!

Railway يدعم PHP/Laravel بشكل كامل + Deploy تلقائي من GitHub.

## 🚀 خطوات النشر (5 دقائق)

### 1. إنشاء حساب Railway

1. اذهب إلى [railway.app](https://railway.app)
2. اضغط **"Login with GitHub"**
3. سجّل دخول بحساب GitHub

### 2. إنشاء Project

1. اضغط **"New Project"**
2. اختر **"Deploy from GitHub repo"**
3. اختر `mazroni9/DASM-ads-manager`
4. Railway سيكتشف Laravel تلقائياً ✅

### 3. إضافة Database

1. في Project Dashboard → **"New"** → **"Database"** → **"Add PostgreSQL"**
2. Railway سيعطيك Database تلقائياً
3. Copy connection string

### 4. إعداد Environment Variables

في Project Settings → Variables:

```env
APP_KEY=base64:YOUR_KEY
APP_ENV=production
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

# Database (Railway يعطيك هذه تلقائياً)
DB_CONNECTION=pgsql
DB_HOST=${{PGHOST}}
DB_PORT=${{PGPORT}}
DB_DATABASE=${{PGDATABASE}}
DB_USERNAME=${{PGUSER}}
DB_PASSWORD=${{PGPASSWORD}}

# DASMe Integration
DASM_API_URL=https://dasm.example.com/api/v1
DASM_API_TOKEN=your-token
ADS_TRACKING_SECRET=your-secret
```

### 5. Build Settings

Railway يكتشف Laravel تلقائياً، لكن تأكد:

- **Build Command**: `composer install --no-dev --optimize-autoloader`
- **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`

### 6. Run Migrations

بعد أول Deploy:

1. في Railway Dashboard → **Deployments** → Latest
2. اضغط **"View Logs"**
3. أو استخدم Railway CLI:
   ```bash
   railway run php artisan migrate
   ```

## ✅ الربط التلقائي

**Railway مربوط تلقائياً مع GitHub!**

- ✅ كل `git push` → Deploy تلقائي
- ✅ Pull Requests → Preview deployments
- ✅ Production → كل push إلى `main`

## 🌐 Custom Domain

في Railway Dashboard → Settings → Domains:
- يمكنك إضافة domain مخصص
- Railway يعطيك HTTPS تلقائياً

## 📊 Monitoring

- **Logs**: Dashboard → Deployments → View Logs
- **Metrics**: CPU, Memory, Network
- **Database**: PostgreSQL Dashboard

## 💰 السعر

- **Free Tier**: $5 credit/شهر
- **Pro**: $20/شهر
- كافي للبدء (Free tier)

---

**Railway = الأسهل والأفضل للـ Laravel!** ⭐
