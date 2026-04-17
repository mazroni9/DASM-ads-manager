# 🔄 إعادة تنظيم المشروع - Project Reorganization

## ✅ الهيكل الموصى به

```
project-root/
├── (Next.js Frontend - Root)
│   ├── app/
│   ├── components/
│   ├── public/
│   ├── package.json
│   ├── next.config.js
│   ├── vercel.json (لـ Next.js)
│   └── ...
│
├── backend/ (Laravel API)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── composer.json
│   ├── artisan
│   └── ...
│
└── README.md
```

---

## 🎯 الخطوات

### 1. إنشاء Next.js Project في Root

```bash
# في root directory (C:\dasm-ads)
cd C:\dasm-ads
npx create-next-app@latest . --typescript --tailwind --app --no-git
```

### 2. نقل Laravel إلى `/backend`

```bash
# نقل المشروع Laravel الحالي إلى /backend
mv C:\dasme-ads-laravel/* C:\dasm-ads\backend\
```

### 3. إعداد Vercel للـ Next.js

Vercel.json في root:
```json
{
  "framework": "nextjs",
  "buildCommand": "npm run build",
  "outputDirectory": ".next"
}
```

### 4. إعداد Laravel للـ Render/Fly

- Render: يحتاج `render.yaml`
- Fly.io: يحتاج `fly.toml`

---

## 📋 الملفات المطلوبة

### 1. `/package.json` (Next.js Root)
```json
{
  "name": "dasme-ads-frontend",
  "version": "1.0.0",
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start"
  },
  "dependencies": {
    "next": "^14.0.0",
    "react": "^18.0.0"
  }
}
```

### 2. `/backend/render.yaml` (للـ Render)
```yaml
services:
  - type: web
    name: dasme-ads-backend
    env: php
    buildCommand: composer install --no-dev --optimize-autoloader
    startCommand: php artisan serve --host=0.0.0.0 --port=$PORT
```

### 3. `/backend/fly.toml` (للـ Fly.io)
```toml
app = "dasme-ads-backend"
primary_region = "iad"

[build]
  builder = "paketobuildpacks/builder:base"

[http_service]
  internal_port = 8000
  force_https = true
```

---

## 🚀 Deployment

### Frontend (Next.js) → Vercel
- ✅ Vercel يدعم Next.js بشكل ممتاز
- ✅ Auto Deploy من GitHub
- ✅ Zero Configuration

### Backend (Laravel) → Render/Fly
- ✅ Render.com (أسهل)
- ✅ Fly.io (أسرع)
- ✅ Railway.app (أفضل للـ Laravel)

---

## 📝 ملاحظات

1. **Environment Variables**: 
   - Next.js في Vercel
   - Laravel في Render/Fly

2. **API URLs**:
   - Next.js يحتاج `NEXT_PUBLIC_API_URL=https://backend.example.com`

3. **CORS**: 
   - Laravel يحتاج CORS config للسماح لـ Next.js

---

**هل تريد مني تنفيذ هذا التنظيم الآن؟**
