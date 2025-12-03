# CMS Orbit Core

A powerful Laravel admin panel and resource management system with a clean, unified architecture.

## Features

- **Settings Dashboard**: Complete admin interface with authentication, notifications, and customizable layouts
- **Resource Management**: CRUD operations with automatic screens and forms
- **UI Components**: Rich set of fields, layouts, and actions for building interfaces
- **Icon System**: Integrated icon management with Bootstrap Icons
- **Filters & Metrics**: Advanced filtering and data visualization
- **Attachments**: File upload and management system
- **Authentication**: Built-in access control and user management

## Installation

```bash
composer require cms-orbit/core
```

## Directory Structure

```
src/
├── CoreServiceProvider.php  # 단일 통합 Service Provider
├── Http/                    # HTTP 레이어 (Controllers, Middleware, Screens, Requests)
│   ├── Controllers/         # 모든 컨트롤러
│   ├── Middleware/          # 미들웨어
│   ├── Screens/            # Screen 컨트롤러
│   ├── Requests/           # Form Requests
│   └── Layouts/            # HTTP Layouts
├── Commands/               # Artisan Commands (전체)
├── Settings/              # Settings Dashboard 기능
│   ├── Dashboard.php      # Dashboard 코어 클래스
│   ├── Components/        # Blade Components
│   ├── Configuration/     # Dashboard 설정 Traits
│   ├── Events/            # Events
│   ├── ItemPermission.php # 권한 관리
│   └── Providers/         # Dashboard Service Providers
├── Resources/             # CRUD Resources
│   ├── Resource.php       # Base Resource 클래스
│   ├── ResourceScreen.php # Base Resource Screen
│   ├── Screens/           # Create, Edit, List, View
│   └── Layouts/           # Resource Layouts
├── UI/                    # UI Components
│   ├── Screen.php         # Base Screen 클래스
│   ├── Field.php          # Base Field 클래스
│   ├── Layout.php         # Base Layout 클래스
│   ├── Actions/           # Button, Link, Menu, etc.
│   ├── Fields/            # Input, Select, Upload, etc.
│   ├── Layouts/           # Table, Card, Modal, etc.
│   ├── Components/        # UI Components
│   ├── Concerns/          # Traits
│   └── Contracts/         # Interfaces
├── Foundation/            # 핵심 기능
│   ├── Icons/             # Icon 시스템
│   ├── Filters/           # Data Filtering
│   ├── Metrics/           # Charts & Metrics
│   └── Attachments/       # File Management
├── Auth/                  # 인증 & 권한
│   ├── Access/            # Access Control
│   └── Models/            # User, Role Models
└── Support/               # 헬퍼 & 유틸리티
    ├── Facades/           # Dashboard, Alert Facades
    ├── Alert/             # Alert 시스템
    └── helpers.php        # Helper Functions
```

## Configuration

설정 파일 발행:

```bash
php artisan vendor:publish --tag=orbit-config
```

설정 파일은 `config/orbit.php`에 생성됩니다.

## Usage

### Resource 생성

```bash
php artisan cms:resource PostResource --model=Post
```

### Screen 생성

```bash
php artisan cms:screen PostListScreen
```

### Resource 등록

`app/Orbit/SettingsProvider.php`:

```php
use CmsOrbit\Core\OrbitServiceProvider;
use CmsOrbit\Core\UI\Actions\Menu;

class SettingsProvider extends OrbitServiceProvider
{
    public function menu(): array
    {
        return [
            Menu::make('Posts')
                ->route('orbit.resource.list', 'posts')
                ->icon('bs.file-text'),
        ];
    }
}
```

## License

MIT
