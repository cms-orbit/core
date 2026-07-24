# Changelog

이 문서는 `cms-orbit/core`의 릴리스 노트를 기록합니다.

## 4.0.12 - 2026-07-24

### 추가

- **패키지 커스텀 관리자 컴포넌트 등록**: `frontend.json`에 `registrations`(alias 상대 모듈 경로 목록)를 선언하면, `orbit:frontend-sync`가 호스트 `resources/js/orbit/registrations.ts` 애그리게이터를 생성하고 `orbit/screen` 브리지가 이를 import 하도록 연결합니다. 패키지는 호스트 파일 수정 없이 `registerComponents(...)`로 관리자 화면용 커스텀 필드/스크린을 기여할 수 있습니다. (`FrontendManifest::registrations()`, `FrontendSync::syncRegistrations()`)

## 4.0.11 - 2026-07-23

### 수정

- 로그인 화면(`orbit/auth/login`)을 공용 `ToastProvider`로 감쌌습니다. 이제 게스트 인증 흐름에서 서버가 남긴 `orbit.flash`(Toast/Alert) 메시지가 관리자 셸과 동일한 토스트 UX로 표시됩니다. 기존에는 로그인 페이지가 인증된 셸(`OrbitProviders`) 바깥이라 `FlashBridge`가 마운트되지 않아, 세션에 플래시된 안내 메시지가 로그인 화면에서 조용히 사라졌습니다. (필드 단위 유효성 오류는 종전대로 입력칸 아래 인라인으로 표시됩니다.)

## 4.0.10 - 2026-07-13

### 추가

- 호스트 `entities/` 런타임 PSR-4 등록 — 호스트 `composer.json`에 `"Entities\\": "entities/"`가 없어도 스캔됩니다.
- `SuperAdminPermissionSync` — 등록 권한 fingerprint가 바뀌면 시스템 super-admin 역할을 자동 갱신합니다(`orbit.permissions.auto_sync_super_admin`, `ORBIT_AUTO_SYNC_SUPER_ADMIN`).
- `orbit:install` 위성 패키지 선택 — 대화형 multiselect 또는 `--with=announcement,popup,sendgo` (saas/blog 제외). 설치 후 migrate/frontend-sync는 새 artisan 서브프로세스로 실행합니다.
- `orbit.analytics.queue` / `ORBIT_ANALYTICS_QUEUE` — 페이지뷰 INSERT를 `RecordPageview` 잡으로 오프로드(기본 동기).

### 개선

- README·Boost 가이드에 Entity 등록 위치, Entity vs DocumentEntity 선택표, install/CI 체크리스트, `visitor_hash`=APP_KEY HMAC·키 로테이션 영향을 보강했습니다.
- `composer.json` `suggest`에 announcement / popup을 추가했습니다.

## 4.0.9 - 2026-07-13

### 수정

- `4.0.8` 태그 커밋의 `composer.json` `version`이 `4.0.7`로 남아 Packagist가 해당 태그를 건너뛰던 문제를 수정했습니다. npm 의존성 자동 병합(`frontend.json` → `orbit:frontend-sync`)과 `orbit:install`의 `npm install`/`npm run build`는 이 버전부터 Packagist로 제공됩니다.

## 4.0.8 - 2026-07-13

> Packagist에 게시되지 않음 (`composer.json` version 불일치). 기능은 `4.0.9`를 사용하세요.

### 추가

- 각 `cms-orbit/*` 패키지의 `resources/orbit/frontend.json`에 `dependencies`·`devDependencies`를 선언할 수 있습니다. `orbit:frontend-sync`가 이를 호스트 `package.json`에 **누락된 항목만** 병합합니다(기존 버전은 절대 덮어쓰지 않음). core는 관리자 프런트엔드가 사용하는 모든 npm 패키지(`react-bootstrap-icons`, `@blocknote/*`, `@codemirror/*`, `@uiw/react-codemirror`, `recharts`, `cropperjs`, `leaflet`, `react-leaflet`, `marked`, `clsx`, `tailwind-merge` 등)를 선언합니다.
- `orbit:install`이 프런트 스캐폴딩 동기화 직후 `npm install` → `npm run build`를 실행해 Vite manifest까지 생성합니다. `--skip-npm` 옵션으로 건너뛸 수 있으며, npm이 없으면 수동 실행 안내를 출력합니다.

### 수정

- 순정 라라벨 호스트에서 `npm run build`가 `Rolldown failed to resolve import "react-bootstrap-icons"`로 실패하고, 이로 인해 Vite manifest가 생성되지 않아 관리자 페이지가 `ViteException`으로 500이 나던 문제를 해결했습니다. 이제 core 설치만으로 빌드가 통과합니다.

## 4.0.7 - 2026-07-13

### 개선

- `orbit:admin`이 계정 생성 후 로그인 식별자와 비밀번호(기본값 사용 시)를 명시적으로 출력합니다. 기본 비밀번호(`orbit1234`)를 몰라 로그인에 실패하던 혼선을 줄였습니다.

## 4.0.6 - 2026-07-13

### 수정

- `orbit:frontend-sync`가 Vite alias를 주입할 때 `import { fileURLToPath } from 'node:url';`를 `vite.config.ts` 상단에 보장합니다. 순정 스타터킷에서 `npm run dev` 시 `ReferenceError: fileURLToPath is not defined`가 발생하던 문제를 해결했습니다.

## 4.0.5 - 2026-07-12

### 추가

- `CmsOrbit\Core\Foundation\Http\Middleware\ShareOrbitInertia` — 메뉴·섹션·권한·브랜딩·알림·미디어·i18n 등 관리자 Inertia 공유 props와 루트 뷰(`orbit::orbit.app`)를 패키지 안에서 제공합니다. 호스트 `HandleInertiaRequests` 수정 없이 관리자 패널이 동작합니다.
- `CmsOrbit\Core\Foundation\Presenters\UserPresenter` — 기본 User 모델의 글로벌 검색 presenter를 패키지에 내장했습니다.
- 강제 비밀번호 변경 스택(`RequirePasswordChange` 미들웨어, `ForcePasswordController`, 폼 요청, `force-password` 페이지)을 core 패키지로 이관했습니다.

### 수정

- `config/orbit.php`·`routes/auth.php`가 호스트 클래스(`App\Http\Middleware\RequirePasswordChange` 등) 대신 패키지 클래스를 참조합니다. 순정 호스트에서 `Target class [App\Http\Middleware\RequirePasswordChange] does not exist` 500 오류를 해결했습니다.
- `orbit:frontend-sync`가 `resolve.alias` 블록이 없는 `vite.config.*`에도 alias 블록을 안전하게 주입하도록 개선했습니다.
- 관리자 CSS를 패키지가 자체 생성(`resources/css/orbit.css`)하도록 전환해, 호스트 `app.css` 수정 없이 Tailwind 소스 스캔·디자인 토큰이 적용됩니다.

## 4.0.4 - 2026-07-06

### 추가

- `CmsOrbit\Core\Analytics\Support\AnalyticsSchemaConnection` — analytics 추적기가 읽고 쓸 DB 연결을 결정하는 확장 지점. 기본값은 앱 기본 연결이며, 선택적 패키지가 `resolveUsing()`으로 호스트 애플리케이션 요청의 연결을 재지정할 수 있습니다.

### 수정

- `AnalyticsTracker`가 `cms-orbit/saas`의 `HostApplicationDomains`·`HostConnection`을 직접 참조하던 결합을 제거했습니다. 이제 core는 saas를 몰라도 동작하므로, saas 미설치 호스트에서 호환용 shim(`app/CmsOrbit/Saas/...`)과 수동 `CmsOrbit\Saas\` autoload 등록이 더 이상 필요하지 않습니다.

## 4.0.2 - 2026-07-06

### 변경

- 브랜딩 설정에서 컬러 프리셋·Primary/Secondary/Accent 필드를 제거하고 **관리자 디자인설정**으로 일원화했습니다.
- 브랜딩설정 UI를 이름·로고·심볼·기본 테마 모드 중심으로 정리했습니다.
- `LayoutThemeRegistry`·`ConfigServiceProvider`의 레이아웃/테마 라벨과 프리셋 옵션에 `__()` 번역을 적용했습니다.

### 개선

- 관리자 디자인설정 레이아웃 미리보기 카드를 sticky로 고정했습니다.
- Boost 가이드라인에 브랜딩(공통)과 레이아웃별 컬러(Admin Design) 역할 분리 설명을 보강했습니다.
- 한글팩에 Admin Design·색상 프리셋·테마 프리셋 이름 번역을 추가했습니다.

### 수정

- SaaS 호스트 요청에서 `AnalyticsTracker`가 `HostConnection` DB를 사용하도록 보정했습니다.

## 4.0.1 - 2026-07-05

### 추가

- Notion형 블록 에디터 `RichText` 필드(BlockNote): `/` 슬래시 메뉴, `#`/`##` 마크다운 단축키, HTML 저장
- `RichTextConverter::toMarkdown()` — 저장된 HTML을 Markdown으로 변환

### 변경

- **Breaking:** `Quill` 필드 및 `quill` React 컴포넌트 제거 → `RichText::make()` 사용
- `DocumentEntity` 기본 content 필드를 RichText로 전환
- 필드 쇼케이스(DemoEntity, ExampleTextEditorsScreen) RichText 반영
- 설정 허브 **브랜딩설정**과 **관리자 디자인설정** 설정 그룹을 분리했습니다

### 제거

- npm `quill` 의존성

## 4.0.0 - 2026-07-05

### 추가

- 설정 허브를 **기본설정·컨텐츠·사용자·API 연동·SaaS** 아코디언 섹션으로 그룹화하고, 접힘 상태를 쿠키로 유지합니다.
- 설정 그룹에 `hubSection` 속성을 도입해 허브 섹션 배치를 선언할 수 있습니다.
- Google·Kakao·Apple 소셜 로그인을 **소셜로그인** 전용 설정 그룹으로 분리했습니다.
- 메뉴 `active` URL 패턴 매칭을 서버 직렬화와 React 셸 양쪽에서 지원합니다.
- `php artisan orbit:frontend-sync` 명령으로 설치된 `cms-orbit/*` 패키지의 Inertia 페이지 브리지와 Vite alias를 자동 생성합니다. `orbit:install`·`orbit:sync` 흐름에 통합했습니다.
- Laravel Boost v2 자동 감지 형식(`resources/boost/guidelines/`, `resources/boost/skills/`)으로 Orbit 가이드라인·스킬(Entity, i18n, 패키지 기여)을 배포합니다.
- 각 패키지가 선언하는 `resources/orbit/frontend.json` manifest 기반 프런트엔드 연결 규약을 도입했습니다.

### 변경

- 4.0.0 정식 릴리스에 맞춰 패키지 마이그레이션을 테이블당 단일 `create` 파일로 통합했습니다. 증분 alter/backfill 마이그레이션을 제거하고, 신규 설치(`migrate:fresh`) 기준 최종 스키마만 유지합니다.
- 호스트 `database/migrations`는 Laravel 기본(users, cache, jobs)만 두고, Orbit 전용 users/roles 확장은 패키지 auto-load 마이그레이션으로 이전했습니다.
- SendGo API 자격 증명 설정을 `cms-orbit/sendgo` 패키지로 이전했습니다(인증·보안 그룹에서 제거).
- 활성화된 로컬 로그인 수단이 없을 때 이메일 로그인을 강제로 표시하지 않습니다.

### 개선

- 연결 계정·로그인 이력·방문 기록·활동 로그 등 접근 제어/분석 화면의 남은 영문 UI를 한글팩으로 보강했습니다. 로그인 이력 탭 필터, 기기 유형, 유입 경로 등 서버·클라이언트 양쪽 번역을 맞췄습니다.
- 엔티티 `label()`/`singularLabel()` 복수·단수 리소스명(Users, Roles 등)이 메뉴·CRUD 메시지에서 영문으로 남던 문제를 수정했습니다. 기본 Entity와 User/Role 엔티티에 `__()`를 적용했습니다.
- README에 **호스트 설정** 표를 추가해 필수/선택 수동 작업을 구분했습니다. 순정 Laravel + Composer 설치만으로 동작하도록 Vite alias·브리지 수동 편집을 줄였습니다.
- Boost 가이드라인에 **패키지 독립성**·**다국어 필수**·호스트 코드 포함 금지 규칙을 명시했습니다.
- `orbit:install` / `orbit:sync`가 Laravel Boost가 이미 설정된 호스트에서 cms-orbit 패키지를 Boost에 등록하고 `boost:update`를 자동 실행합니다.
- README에 Laravel Boost 흐름, `orbit:admin`, `npm run dev` 안내를 보강했습니다.
- 소셜 로그인만 활성화된 호스트에서 로그인 화면이 소셜 버튼만 표시되도록 정리했습니다.
- 로그인 수단이 전혀 구성되지 않았을 때 안내 메시지를 표시합니다.
- `cms-orbit/sendgo` suggest 설명을 SendGo 관리자 GUI·템플릿 동기화·캠페인 기록 기준으로 갱신했습니다.

## 4.0.0-beta4 - 2026-07-04

### 추가

- 브랜딩 자산에 라이트/다크 전용 로고와 심볼을 각각 연결할 수 있도록 확장하고, 브랜딩 이미지 업로드를 크롭 가능한 전용 필드로 정리했습니다.
- 파비콘 PNG 업로드 시 `apple-touch-icon`, `192/512` 아이콘, 웹 매니페스트까지 함께 생성해 브라우저별 메타 링크를 자동으로 채울 수 있는 기반을 추가했습니다.
- 활동 로그, 로그인 이력, 방문 기록, 대시보드 분석 요약을 Orbit 관리자 안에서 바로 확인할 수 있는 감사/분석 화면 기반을 추가했습니다.

### 개선

- 관리자 레이아웃의 테마 모드 전환을 드롭다운 단일 액션으로 통합하고, 저장 직후 `dark` 클래스와 브랜드 자산이 즉시 반영되도록 반응 흐름을 정리했습니다.
- 공통 버튼, 입력, 섹션, 테이블, 배지, 드롭다운, 빈 상태, 오버레이가 Orbit 디자인 토큰을 직접 사용하도록 맞춰 레이아웃/브랜딩 색상과 폼 UI가 더 일관되게 따라오도록 개선했습니다.
- 디자인 설정 미리보기에서 현재 프리뷰 톤에 맞는 라이트/다크 로고/심볼을 선택해 실제 셸과 더 비슷하게 검증할 수 있게 했습니다.
- 사이드바 섹션 헤더와 메뉴 그룹 헤더를 토큰 수준에서 분리해 네비게이션 정보 계층과 대비를 네 가지 레이아웃 전반에서 더 선명하게 정리했습니다.
- 이제 더 이상 활동/분석/권한 화면을 열었을 때 영어와 한국어가 섞여 보여 운영자가 의미를 추측하지 않아도, 대시보드 요약과 감사 로그, 미디어 라이브러리, 셸 공통 UI 전반에서 한국어 메시지가 더 촘촘하게 이어지도록 한글팩을 보강했습니다.
- 공지/팝업/블로그/SaaS 패키지와 함께 쓰는 공통 컴포넌트의 문구 기준을 맞춰, 패키지별 화면을 오갈 때 번역 톤이 들쭉날쭉하지 않도록 정리했습니다.

### 수정

- 다크/라이트 모드 전환 후 여러 번 페이지를 이동해야 적용되던 문제를 수정했습니다.
- Orbit 화면 기본 문서 제목이 `Laravel`로만 남던 흐름을 정리해 화면 제목이 브라우저 탭에 반영되도록 수정했습니다.
- 업로드/알림/언어 전환/크로퍼 등 새 UI 문구와 디자인 설정 설명 중 남아 있던 한국어 미번역 키를 보강했습니다.
- 미디어 라이브러리, 선택 필드, 차트 빈 상태, 지도/크로퍼 placeholder, 행렬 필드, 색상 선택기처럼 새로 추가된 프런트 컴포넌트들에 하드코딩 영어 문구가 남아 있던 문제를 수정했습니다.

## 4.0.0-beta3 - 2026-07-04

### 안내

- 이제 설치 흐름, Entity 등록 방식, 문서형 콘텐츠 확장 포인트를 README에서 바로 확인할 수 있도록 사용 문서를 전면 보강했습니다.

### 개선

- 이제 더 이상 Orbit 셸 색상을 맞추기 위해 상단바, 사이드바, 카드 컴포넌트를 각각 따로 덮어쓰지 않아도, 공통 디자인 토큰만 맞추면 페이지 배경과 헤더, 내비게이션, 패널 톤이 함께 따라오도록 정리했습니다.
- 싱글 레이아웃과 탑바 레이아웃 모두 `orbit.home` 값을 기준으로 브랜드 홈 링크를 따라가도록 맞춰, 레이아웃마다 홈 이동 경로가 어긋나지 않게 개선했습니다.
- 패키지 버전 메타데이터를 `4.0.0-beta3` 기준으로 정리해 다른 Orbit 패키지와 릴리스 라인을 한눈에 맞추기 쉬워졌습니다.

### 수정

- 상단바/사이드바/카드가 일부 테마에서 고정 색상 클래스에 묶여 보여 커스텀 브랜딩이 부분적으로만 반영되던 문제를 수정했습니다.
- 홈 링크가 `/` 또는 레이아웃 기본값으로 남아 있어 운영자가 의도한 대시보드 시작점으로 돌아가지 못하던 흐름을 정리했습니다.

## 4.0.0-beta2 - 2026-07-04

### 추가

- 설정 그룹, 디자인 설정, SEO 설정을 더 세밀하게 조합할 수 있는 설정 화면 확장 포인트를 추가했습니다.
- 코드, 컬러, 크로퍼, 지도, Quill 기반 입력을 포함한 필드 렌더링을 정리하고 클라이언트 전용 구현을 분리했습니다.
- Demo 섹션에 필드, 레이아웃, 카드, 차트, 액션, 그리드 예제를 대폭 추가해 새 화면을 붙이기 전에 동작 방식을 바로 확인할 수 있도록 했습니다.
- 레이아웃/SEO 프리뷰, 테마 미리보기, 데모 블록 뷰 등 실전 설정 화면을 빠르게 검증할 수 있는 리소스를 보강했습니다.

### 개선

- 이제 더 이상 새 설정 화면을 붙일 때 어디까지가 Core가 담당하는 UI 계약인지 추측하지 않아도, 예제 화면과 설정 레지스트리를 기준으로 화면 구조를 빠르게 복제할 수 있도록 개선되었습니다.
- 패키지들이 관리자 메뉴 섹션을 스스로 등록할 수 있도록 기반을 다듬어, 기능 패키지 추가 시 Core 메뉴 구조를 덜 건드려도 되게 만들었습니다.

### 수정

- 설정/레이아웃 화면에서 필드 계약이 분산되어 있어 동일한 UI를 반복 구현해야 하던 흐름을 정리했습니다.
- 브랜딩, 레이아웃, 다국어 라벨 연결 지점이 흩어져 있어 확장 패키지에서 재사용하기 어렵던 부분을 보완했습니다.
