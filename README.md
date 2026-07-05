# CMS Orbit Core

`cms-orbit/core`는 Laravel용 Orbit 관리자 엔진의 중심 패키지입니다.  
PHP에서 정의한 `Screen / Layout / Field / Entity` 계약을 Inertia + React로 렌더링해, CRUD 관리자 화면과 문서형 콘텐츠, 설정 화면, 메뉴, 권한 구성을 한 흐름으로 묶어줍니다.

## 무엇을 제공하나요?

- `Entity` 기반의 선언형 관리자 CRUD
- `Screen / Layout / Field` 기반의 서버 주도형 UI 계약
- `DocumentEntity` 기반의 문서형 콘텐츠 엔진
- `orbit_config()`와 설정 레지스트리를 통한 사이트 설정 관리
- Orbit 셸, 메뉴, 브랜딩, Breadcrumb, 검색, 권한 연동
- 라이트/다크 전환, 레이아웃별 디자인 토큰, 브랜딩 로고/심볼 교체, 파비콘 변형 생성까지 포함한 관리자 테마 시스템
- 예제 화면과 필드 확장을 바로 확인할 수 있는 Demo 섹션
- 외부 분석 도구 없이 동작하는 내장 방문 통계(페이지뷰·방문자·국가·리퍼러)

## 요구사항

- PHP `^8.3`
- Laravel `^11.0 || ^12.0 || ^13.0`
- Inertia Laravel `^3.0`

## 설치

```bash
composer require cms-orbit/core:^4.0
php artisan orbit:install
```

`orbit:install`은 설정/마이그레이션/스텁 게시, `entities/`·`OrbitProvider` 준비, 설치된 `cms-orbit/*` 패키지의 Inertia/Vite 연결(`orbit:frontend-sync`), AI 가이드 배포(`orbit:ai`)까지 한 번에 처리합니다.

Laravel Boost를 쓰는 프로젝트라면 설치 후 `php artisan boost:install`을 실행해 각 패키지의 `resources/boost` 가이드라인·스킬을 병합할 수 있습니다.

## 호스트 설정 (수동 작업 최소화)

| 작업 | 필수 여부 | 설명 |
| --- | --- | --- |
| `php artisan orbit:install` | **필수** (최초 1회) | 설정, 마이그레이션, User/OrbitProvider 스텁, 프런트 브리지 |
| `php artisan orbit:frontend-sync` | 패키지 추가/제거 시 | `resources/orbit/frontend.json` 기준 Vite alias·Inertia 브리지 자동 생성 |
| `php artisan orbit:sync` | 선택 | 설정/스텁만 안전하게 재동기화 (User 모델은 기본적으로 덮어쓰지 않음) |
| `vite.config.*` 수동 alias | **불필요** | `orbit:frontend-sync`가 `// ORBIT:ALIASES:START` 블록을 관리 |
| `resources/js/pages/*` 수동 re-export | **불필요** | 위 sync 명령이 패키지 페이지 브리지를 생성 |
| Entity를 `app/Orbit/OrbitProvider`에 등록 | 호스트 전용 Entity 사용 시 | 패키지 Entity는 Service Provider가 자동 등록 |

기존 Laravel `User` 모델을 유지하려면 설치 중 덮어쓰기 확인에서 **아니오**를 선택하고, 안내되는 trait/extends 호환 가이드를 따르세요.


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

### 브랜딩 업로드와 파비콘

브랜딩 설정에서는 라이트/다크 로고와 심볼을 각각 연결할 수 있고, 파비콘 이미지는 크롭 후 업로드할 수 있습니다. PNG 파비콘을 올리면 브라우저 메타 링크에 사용할 수 있는 `apple-touch-icon`, `192/512` PNG, 웹 매니페스트까지 함께 생성되도록 설계되어 있습니다.

### 방문 통계 (Analytics)

Orbit Core는 `CaptureAnalytics` 미들웨어와 `orbit_analytics_pageviews` 테이블로 가벼운 방문 분석을 내장합니다. 별도 SaaS 없이 페이지뷰·방문자·리퍼러·디바이스·국가를 수집하고, 관리자 대시보드와 **Visitor Records** 화면에서 확인할 수 있습니다.

#### 수집 항목

각 페이지뷰( GET HTML/Inertia 응답 )마다 다음을 저장합니다.

| 항목 | 설명 |
|------|------|
| 페이지 | `page_path`, `route_name`, `route_uri`, 유입(`is_entrance`) |
| 방문자 식별 | `visitor_hash`(쿠키 UUID의 HMAC), `visit_token`(30분 세션) |
| 사용자 | 로그인 시 `user_id`, `user_type`, `user_name`, `user_email` 귀속 |
| 클라이언트 | `browser_family`, `device_type`, `user_agent`, `is_bot` |
| 유입 | `referrer_host`(동일 호스트는 Direct) |
| 네트워크 | `ip_address`(IPv4 마지막 옥텟·IPv6 접두사 익명화), `country_code`(ISO 3166-1 alpha-2) |
| 범위 | `instance_id`(멀티 인스턴스 앱), `visited_on` |

원본 방문자 UUID는 DB에 저장하지 않습니다. `visitor_hash`만 보관합니다.

#### 수집하지 않는 요청

기본값 기준으로 아래 요청은 집계에서 제외됩니다.

- **관리자 라우트** — `analytics.exclude_admin_routes`가 켜져 있으면 `orbit.*` 라우트
- **봇** — User-Agent 패턴 또는 `analytics.filter_bots`로 필터
- **Do Not Track** — `DNT: 1` 헤더(`analytics.respect_do_not_track`)
- **비 GET** — POST·PUT 등 변경 요청
- **비 HTML** — JSON API( Inertia `X-Inertia` 제외 ), 3xx/4xx/5xx 응답
- **비 라우트** — 라우트가 매칭되지 않은 요청
- **수집 비활성** — `analytics.enabled`가 꺼져 있거나 `orbit_analytics_pageviews` 테이블이 없을 때

#### Visitor Records (관리자)

**Users & Roles** 섹션의 **Visitor Records** 엔티티로 원시 페이지뷰를 조회합니다.

- **목록** — 최근 30일 메트릭 카드(페이지뷰, 인기 페이지·리퍼러·디바이스), 검색, 테이블 필터(방문 기간, Audience: 전체/게스트/로그인 사용자)
- **상세** — 방문·방문자·네트워크 정보와 동일 `visitor_hash`의 **Other visits by this visitor** 이력
- **Scope** — `instance_id`가 `null`이면 **Host**, 값이 있으면 **Instance** 배지

인스턴스 컨텍스트가 있는 관리자에서는 해당 인스턴스 기록과 호스트(`instance_id` null) 기록을 함께 볼 수 있습니다.

#### 게스트 → 로그인 귀속

로그인 성공 시(`Login` 이벤트):

1. **귀속** — 현재 `orbit_analytics_visit` 토큰(또는 없으면 최근 10분 `visitor_hash`)으로 저장된 미귀속 페이지뷰에 사용자 정보를 채웁니다.
2. **쿠키 교체** — `rotateIdentityAfterLogin()`으로 방문자·방문 쿠키를 새 UUID로 교체해, 로그인 전후 세션을 분리합니다.

로그아웃 시 `forgetIdentityAfterLogout()`으로 두 쿠키를 삭제합니다. 로그인·로그아웃 감사 로그는 **Authentication & Security** 설정 그룹과 연동된 활동 로그에도 남습니다.

#### 쿠키

| 쿠키 | 수명 | 용도 |
|------|------|------|
| `orbit_analytics_visitor` | 395일 | 장기 방문자 식별( DB에는 HMAC 해시만 저장 ) |
| `orbit_analytics_visit` | 30분 | 단일 방문(세션) 구분, 입장 페이지 판별 |

두 쿠키는 `HttpOnly`, `SameSite=Lax`, 경로 `/`로 설정됩니다. Laravel `EncryptCookies` 미들웨어 예외 목록에 등록되어 **암호화되지 않습니다** — 미들웨어가 요청마다 쿠키 값을 읽어야 하기 때문입니다.

#### 국가 코드

`AnalyticsGeoLocator`가 국가를 결정합니다. 우선순위:

1. **프록시/엣지 헤더** — `CF-IPCountry`(Cloudflare), `CloudFront-Viewer-Country`, `X-AppEngine-Country`, `Fly-Client-Country`, `X-Vercel-IP-Country` 등. `ORBIT_ANALYTICS_COUNTRY_HEADERS`로 추가 헤더 지정 가능
2. **MaxMind GeoIP** — 로컬 `.mmdb` DB로 공인 IP 조회( IP 원본은 저장하지 않음 )
3. **PHP geoip 확장**(선택) — `geoip_country_code_by_name()`( IP 원본은 저장하지 않음 )
4. **로컬 개발 폴백** — `APP_ENV=local`일 때만 `ORBIT_ANALYTICS_DEV_COUNTRY`(예: `KR`) 적용

##### Cloudflare vs GeoIP

| 방식 | 적합한 경우 | 장점 | 단점 |
|------|-------------|------|------|
| **Cloudflare 등 엣지 헤더** | CDN·리버스 프록시 앞에 두는 프로덕션 | 추가 패키지·DB 없음, 요청당 조회 비용 없음 | Cloudflare 등 인프라 필요 |
| **MaxMind GeoIP** | 자체 서버·VPS, 헤더 없는 환경 | 인프라 독립, 오프라인 조회 | DB 다운로드·갱신 필요, MaxMind 무료 계정 등록 |
| **개발 폴백** | Valet·localhost | 설정 한 줄로 테스트 가능 | `local` 환경에서만 동작 |

프로덕션에서는 Cloudflare, CloudFront, Fly.io, Vercel 등 앞단에서 국가 헤더가 전달되도록 배치하는 것을 권장합니다. CDN/프록시 없이 직접 서빙할 때는 MaxMind GeoIP를 활성화하세요.

##### MaxMind GeoLite2 설정

GeoIP는 **요청 시점의 전체 IP**로만 조회하고, DB 저장 시에는 기존과 같이 익명화된 IP만 저장합니다.

1. [MaxMind](https://www.maxmind.com/en/geolite2/signup)에서 무료 계정을 만듭니다(GeoLite2 End User License Agreement 동의 필요).
2. 계정에서 **License key**를 발급합니다.
3. GeoLite2 Country DB를 다운로드합니다:

```bash
mkdir -p storage/app/geoip
curl -L "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=YOUR_LICENSE_KEY&suffix=tar.gz" \
  | tar -xz --strip-components=1 -C storage/app/geoip --wildcards '*/GeoLite2-Country.mmdb'
```

4. `.env`에 다음을 추가합니다:

```env
ORBIT_ANALYTICS_GEOIP_ENABLED=true
ORBIT_ANALYTICS_GEOIP_DATABASE_PATH=/absolute/path/to/storage/app/geoip/GeoLite2-Country.mmdb
```

`ORBIT_ANALYTICS_GEOIP_DATABASE_PATH`를 생략하면 기본값 `storage/app/geoip/GeoLite2-Country.mmdb`를 사용합니다.

5. DB는 MaxMind 정책에 따라 주기적으로 갱신하세요(권장: 월 1회). cron 예시:

```bash
0 3 1 * * curl -L "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=YOUR_LICENSE_KEY&suffix=tar.gz" | tar -xz --strip-components=1 -C /path/to/storage/app/geoip --wildcards '*/GeoLite2-Country.mmdb'
```

**로컬 개발(헤더·GeoIP DB 없을 때)** — `ORBIT_ANALYTICS_DEV_COUNTRY=KR`로 시뮬레이션하거나, MaxMind 테스트 DB(`packages/cms-orbit/core/tests/fixtures/GeoIP2-Country-Test.mmdb`)를 경로에 지정해 `81.2.69.160` 같은 공인 IP로 조회를 확인할 수 있습니다.

Valet·localhost처럼 위 방법을 모두 쓰지 않으면 **`country_code`는 `Unknown`(null)** 으로 표시됩니다.

#### 설정

Orbit 설정 화면에서 인스턴스별로 덮어쓸 수 있습니다.

**Analytics (방문 통계)**

| 키 | 기본값 | 설명 |
|----|--------|------|
| `analytics.enabled` | `true` | 페이지뷰 수집 on/off |
| `analytics.exclude_admin_routes` | `true` | Orbit 관리자 라우트 제외 |
| `analytics.filter_bots` | `true` | 봇 User-Agent 제외 |
| `analytics.respect_do_not_track` | `true` | DNT 헤더 존중 |
| `analytics.retention_days` | `90` | 보존 기간(일). 만료분은 하루 1회 자동 삭제 |

**Authentication & Security (인증 및 보안)** — 방문 통계 전용 스위치는 없지만, 로그인·로그아웃 시 방문 귀속과 쿠키 교체·삭제, 로그인 잠금(`auth_security.*`) 등 인증 이벤트가 방문 기록과 함께 동작합니다.

환경 변수(`config/orbit.php`):

```env
ORBIT_ANALYTICS_DEV_COUNTRY=KR
ORBIT_ANALYTICS_COUNTRY_HEADERS=X-Custom-Country
ORBIT_ANALYTICS_GEOIP_ENABLED=true
ORBIT_ANALYTICS_GEOIP_DATABASE_PATH=/absolute/path/to/GeoLite2-Country.mmdb
```

#### 호스트 vs 인스턴스

`instance_context()`가 없는 요청(예: `orbit.test` 호스트 전역 트래픽)은 `instance_id = null`로 저장됩니다. 멀티 테넌트 인스턴스 URL에서는 해당 인스턴스 ID가 붙습니다. Visitor Records의 **Scope** 열과 필터로 구분합니다.

#### 마이그레이션

`php artisan orbit:install`(또는 `php artisan migrate`) 시 패키지 마이그레이션이 `orbit_analytics_pageviews` 테이블과 사용자 귀속·네트워크(`ip_address`, `country_code`)·`user_agent` 컬럼을 순차 적용합니다. 기존 설치에 Core를 올릴 때는 마이그레이션만 실행하면 됩니다.

## 테스트와 점검

```bash
composer validate --no-check-publish
```

호스트 앱 전체 동작까지 함께 확인하려면 루트 애플리케이션에서 타입 체크와 Laravel 테스트를 같이 돌리는 것을 권장합니다.

## License

MIT
