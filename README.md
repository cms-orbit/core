# CMS Orbit Core

> 🚀 **강력한 Laravel 기반 CMS 패키지**

Orbit 3.1의 핵심 엔티티 시스템을 계승하고, 모듈형 아키텍처로 재탄생한 CMS 플랫폼

---

## ✨ 주요 기능

### 🎯 엔티티 시스템

**DynamicModel** - 기본 CRUD 엔티티
- UUID, SoftDeletes, Translations
- Sorting, ActivityLog
- 권한 자동 생성

**DocumentModel** - 문서형 엔티티
- 다국어 콘텐츠 자동 관리
- 슬러그, SEO 최적화
- 조회수, 추천수, 댓글수
- 작성자 정보 (Polymorphic)

### 🔧 CLI Commands

```bash
cms:entity Product              # DynamicModel 엔티티 생성 (마이그레이션 생성 여부 물어봄)
cms:entity Product -m           # 마이그레이션 자동 생성 (-m = --migration)
cms:document Blog -m            # DocumentModel 문서 생성 + 마이그레이션
cms:model Review --entity=Article -m  # 종속 모델 생성 + 마이그레이션
cms:migration create_products_table --entity=Product --type=dynamic --create=products
cms:admin-fresh                 # 권한 자동 갱신
cms:build-config                # Vite/Tailwind 설정 생성

# 외부 패키지 지원
cms:entity Product --package=vendor/my-package
cms:model Comment --entity=Product --package=vendor/my-package
```

### 🌐 SEO 지원

- ralphjsmit/laravel-seo 통합
- HasSeo trait - 자동 SEO 데이터 생성
- HasSitemap trait - Sitemap 자동 포함
- FrontendHandler - SEO 최적화 렌더링

### 🎨 레이아웃 확장

- Layout::vue() - Vue 컴포넌트 삽입
- 완전한 UI 컴포넌트 라이브러리

### 📦 외부 패키지 지원

- OrbitPackage Facade - 자원 등록 시스템
- --package 옵션으로 어디든 생성

---

## 🚀 설치

### 1. 패키지 설치

```bash
composer require cms-orbit/core
```

### 2. 마이그레이션

```bash
php artisan migrate
```

### 3. 관리자 생성

```bash
php artisan cms:admin
```

---

## 📖 기본 사용법

### 엔티티 생성

```bash
# 기본 엔티티 (마이그레이션 생성 여부 물어봄)
php artisan cms:entity Product
# → Do you want to create a migration? (yes/no) [yes]:

# 또는 -m 옵션으로 자동 생성
php artisan cms:entity Product -m
php artisan migrate
php artisan cms:admin-fresh

# 문서 엔티티
php artisan cms:document Article -m
php artisan migrate
php artisan cms:admin-fresh

# 종속 모델 생성 (hasMany 관계 등)
php artisan cms:model Review --entity=Article -m
php artisan migrate
```

### Frontend 렌더링 (SEO 자동 지원)

```php
use CmsOrbit\Core\Frontend\FrontendHandler;

Route::get('/blog/{slug}', function ($slug) {
    $blog = Blog::where('slug', $slug)->firstOrFail();
    
    // SEO 자동 지원
    return FrontendHandler::render('Blog/Show', [
        'blog' => $blog
    ], $blog);
});
```

### Layout에서 Vue 컴포넌트 사용

```php
use CmsOrbit\Core\Support\Facades\Layout;

public function layout(): iterable
{
    return [
        Layout::rows([
            Input::make('title'),
            TextArea::make('content'),
        ]),
        
        Layout::vue('MyComponent', [
            'prop1' => 'value1',
            'prop2' => 'value2',
        ]),
    ];
}
```

---

## 🔧 Vite 통합 (프론트엔드 사용 시)

### 1. Config 파일 생성

```bash
php artisan cms:build-config
```

이 명령어는 다음 파일들을 자동 생성합니다:
- `packages/cms-orbit/core/resources/js/lib/vite.js`
- `packages/cms-orbit/core/resources/js/lib/tailwind.js`
- `packages/cms-orbit/core/resources/js/lib/alias.js`

### 2. vite.config.js 수정

```javascript
import { defineConfig } from 'vite';
import laravel from '@laravel/vite-plugin';
import react from '@vitejs/plugin-react';
import { viteConfig } from '@cms-orbit/core/lib/vite';

export default defineConfig({
    ...viteConfig,
    plugins: [
        laravel({
            input: ['resources/js/app.tsx', ...viteConfig.input],
            refresh: true,
        }),
        react(),
    ],
});
```

### 3. tailwind.config.js 수정

```javascript
import { tailwindConfig } from '@cms-orbit/core/lib/tailwind';

export default {
    content: [
        './resources/**/*.tsx',
        './resources/**/*.blade.php',
        ...tailwindConfig.content,
    ],
    // ... 나머지 설정
};
```

### 4. jsconfig.json (IDE 지원)

```json
{
    "compilerOptions": {
        "baseUrl": ".",
        "paths": {
            "@/*": ["resources/js/*"],
            "@cms-orbit/core/*": ["packages/cms-orbit/core/resources/js/*"]
        }
    }
}
```

---

## 📦 외부 패키지 개발

### ServiceProvider에서 자원 등록

```php
use CmsOrbit\Core\Support\Facades\OrbitPackage;

class MyPackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Vite 경로 등록
        OrbitPackage::registerPath(
            '@my-package',
            __DIR__.'/../resources/js'
        );
        
        // Vite Entry Point 등록
        OrbitPackage::registerViteEntry('my-package/app.js');
        
        // Tailwind Content 등록
        OrbitPackage::registerTailwindContent(
            __DIR__.'/../resources/**/*.blade.php'
        );
        
        // 커스텀 필드 등록
        OrbitPackage::registerField('MyField', MyField::class);
        
        // 엔티티 등록
        OrbitPackage::registerEntity('MyEntity', MyEntity::class);
    }
}
```

### 패키지에 엔티티 생성

```bash
php artisan cms:entity Product --package=vendor/my-package
php artisan cms:model Comment --entity=Product --package=vendor/my-package
php artisan cms:migration create_products_table --entity=Product --package=vendor/my-package --type=dynamic --create=products
```

---

## 🏗️ 프로젝트 구조

```
app/Entities/
├── Product/
│   ├── Product.php              # DynamicModel
│   ├── Review.php               # 종속 모델
│   ├── database/
│   │   └── migrations/          # 엔티티별 마이그레이션
│   │       ├── 2024_01_01_000001_create_products_table.php
│   │       └── 2024_01_01_000002_create_reviews_table.php
│   ├── Screens/
│   │   ├── ProductListScreen.php
│   │   └── ProductEditScreen.php
│   ├── Layouts/
│   │   ├── ProductListLayout.php
│   │   └── ProductEditLayout.php
│   └── routes/
│       └── orbit.php
│
└── Article/
    ├── Article.php              # DocumentModel
    ├── Presenters/
    │   └── ArticlePresenter.php
    ├── database/
    │   └── migrations/
    │       └── 2024_01_01_000003_create_articles_table.php
    ├── Screens/
    │   ├── ArticleListScreen.php
    │   └── ArticleEditScreen.php
    ├── Layouts/
    │   ├── ArticleListLayout.php
    │   └── ArticleEditLayout.php
    └── routes/
        └── orbit.php
```

---

## 🎨 Vite & Tailwind 통합

### 설정 파일 자동 생성

```bash
php artisan cms:build-config
```

이 명령어는 다음 파일들을 생성합니다:
- `packages/cms-orbit/core/resources/js/lib/vite.js`
- `packages/cms-orbit/core/resources/js/lib/tailwind.js`
- `packages/cms-orbit/core/resources/js/lib/alias.js` (IDE용)

### vite.config.js 설정

```javascript
import { defineConfig } from 'vite';
import laravel from '@laravel/vite-plugin';
import react from '@vitejs/plugin-react';
import { viteConfig } from '@cms-orbit/core/lib/vite';

export default defineConfig({
    ...viteConfig,
    plugins: [
        laravel({
            input: ['resources/js/app.tsx', ...viteConfig.input],
            refresh: true,
        }),
        react(),
    ],
});
```

### tailwind.config.js 설정

```javascript
import { tailwindConfig } from '@cms-orbit/core/lib/tailwind';

export default {
    content: [
        './resources/**/*.tsx',
        './resources/**/*.blade.php',
        ...tailwindConfig.content,
    ],
    // ... 나머지 설정
};
```

### 방법 3: 컴포넌트 직접 등록 (간단한 경우)

```javascript
// vite.config.js
resolve: {
    alias: {
        '@': path.resolve(__dirname, 'resources/js'),
        '@orbit': path.resolve(__dirname, 'vendor/cms-orbit/core/resources/js'),
    },
}
```

Vue에서 사용:
```vue
<script setup>
import MyComponent from '@orbit/components/MyComponent.vue';
import PackageComponent from '@my-package/components/PackageComponent.vue';
</script>
```

---

## 📚 문서

자세한 내용은 패키지 디렉터리의 문서를 참고하세요.

---

## 📊 통계

- **242개** PHP 파일
- **13개** Stub 파일
- **19개** Artisan Commands
- **5개** Traits
- **4개** Models

---

## 📄 라이선스

MIT

---

Made with ❤️ by Amuz Corp
