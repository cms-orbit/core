# CMS Orbit Core

`cms-orbit/core`는 Laravel용 Orbit 관리자 엔진의 중심 패키지입니다.  
PHP에서 정의한 `Screen / Layout / Field / Entity` 계약을 Inertia + React로 렌더링해, CRUD 관리자 화면과 문서형 콘텐츠, 설정 화면, 메뉴, 권한 구성을 한 흐름으로 묶어줍니다.

## 무엇을 제공하나요?

- `Entity` 기반의 선언형 관리자 CRUD
- `Screen / Layout / Field` 기반의 서버 주도형 UI 계약
- `DocumentEntity` 기반의 문서형 콘텐츠 엔진
- `orbit_config()`와 설정 레지스트리를 통한 사이트 설정 관리
- Orbit 셸, 메뉴, 브랜딩, Breadcrumb, 검색, 권한 연동
- 예제 화면과 필드 확장을 바로 확인할 수 있는 Demo 섹션

## 요구사항

- PHP `^8.3`
- Laravel `^11.0 || ^12.0 || ^13.0`
- Inertia Laravel `^3.0`

## 설치

```bash
composer require cms-orbit/core:^4.0@beta
php artisan orbit:install
```

필요하면 에셋과 설정을 따로 퍼블리시할 수 있습니다.

```bash
php artisan vendor:publish --tag=orbit-assets
php artisan vendor:publish --tag=orbit-config
```

## 빠른 시작

### 1. Entity 등록

Orbit는 `EntityRegistry`에 등록된 엔티티를 기준으로 관리자 메뉴, 권한, CRUD 라우트와 화면을 구성합니다.

```php
use App\Orbit\Entities\PostEntity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;

$this->app->afterResolving(EntityRegistry::class, function (EntityRegistry $registry): void {
    $registry->registerClass([PostEntity::class]);
});
```

### 2. Entity 정의

간단한 Entity는 다음과 같은 형태로 시작할 수 있습니다.

```php
use App\Models\Post;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\TD;

class PostEntity extends Entity
{
    public function model(): string
    {
        return Post::class;
    }

    public function fields(): array
    {
        return [
            Input::make('title')->title(__('Title'))->required(),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))->sort(),
            TD::make('title', __('Title')),
        ];
    }
}
```

### 3. 문서형 콘텐츠 만들기

공지사항, 팝업처럼 공용 문서 테이블을 활용하는 콘텐츠는 `DocumentEntity`를 상속하면 됩니다. Orbit가 다국어 문서 레이어, CRUD 흐름, 공개 URL 연결에 필요한 기반을 제공합니다.

### 4. 설정 화면과 Demo 확인

설치 후 개발 환경에서는 Demo 섹션이 자동으로 등록되어 필드/레이아웃/차트/설정 예제를 바로 둘러볼 수 있습니다. 필요하면 `ORBIT_DEMO=false` 또는 `orbit.demo.enabled` 설정으로 끌 수 있습니다.

## 핵심 개념

### Entity

Eloquent 모델을 Orbit 관리자 화면에 연결하는 선언형 설명자입니다. 필드, 컬럼, 보기(legend), 메뉴 섹션, 정렬 순서, 권한 포인트를 한 클래스에 모을 수 있습니다.

### Screen / Layout / Field

PHP 빌더에서 만든 계약을 JSON으로 직렬화한 뒤 React 컴포넌트가 렌더링합니다. 기본 필드로 커버되지 않는 경우 커스텀 필드나 레이아웃을 추가 등록할 수 있습니다.

### Document 엔진

문서형 콘텐츠는 공용 `documents` / `document_contents` 구조를 사용합니다. 공지, 팝업, 배너처럼 에디터 기반 콘텐츠를 패키지 단위로 확장하기 좋습니다.

### Orbit 셸과 디자인 토큰

Orbit 셸은 상단바, 사이드바, 카드, 페이지 배경을 CSS 변수 기반 색상 토큰으로 렌더링합니다. 따라서 셸 테마를 맞출 때 개별 컴포넌트 클래스를 일일이 덮어쓰지 않아도 공통 톤을 맞추기 쉽습니다.

## 테스트와 점검

```bash
composer validate --no-check-publish
```

호스트 앱 전체 동작까지 함께 확인하려면 루트 애플리케이션에서 타입 체크와 Laravel 테스트를 같이 돌리는 것을 권장합니다.

## License

MIT
