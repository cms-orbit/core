<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands\Support;

final class InstallMessages
{
    /**
     * @param array<string, string> $replace
     */
    public function __construct(private readonly InstallLocale $locale) {}

    public static function for(InstallLocale $locale): self
    {
        return new self($locale);
    }

    public function locale(): InstallLocale
    {
        return $this->locale;
    }

    /**
     * @param array<string, string|int> $replace
     */
    public function get(string $key, array $replace = []): string
    {
        $catalog = self::catalog();
        $message = $catalog[$this->locale->value][$key]
            ?? $catalog[InstallLocale::English->value][$key]
            ?? $key;

        foreach ($replace as $name => $value) {
            $message = str_replace(':'.$name, (string) $value, $message);
        }

        return $message;
    }

    /**
     * @return list<string>
     */
    public function guideLines(string $key): array
    {
        $catalog = self::catalog();
        $lines = $catalog[$this->locale->value][$key]
            ?? $catalog[InstallLocale::English->value][$key]
            ?? [];

        return is_array($lines) ? $lines : [];
    }

    /**
     * @return array<string, array<string, string|list<string>>>
     */
    private static function catalog(): array
    {
        return [
            InstallLocale::Korean->value => [
                'started'                             => 'Orbit 설치를 시작합니다. 잠시만 기다려 주세요...',
                'version'                             => '버전: :version',
                'step_publish'                        => '설정, 마이그레이션, 스텁, 에셋을 게시합니다...',
                'step_migrate'                        => '데이터베이스 마이그레이션을 실행합니다...',
                'step_storage_link'                   => 'storage 심볼릭 링크를 생성합니다...',
                'step_user_model'                     => 'User 모델을 구성합니다...',
                'step_namespace_replace'              => '앱 코드의 User 네임스페이스 참조를 정리합니다...',
                'step_entities'                       => 'entities 디렉터리를 준비합니다...',
                'step_orbit_provider'                 => 'OrbitProvider를 준비합니다...',
                'step_frontend_sync'                  => '설치된 Orbit 패키지의 Inertia/Vite 연결을 동기화합니다...',
                'step_ai'                             => 'AI 에이전트 스킬을 배포합니다...',
                'entities_created'                    => '루트 /entities 디렉터리를 생성했습니다.',
                'orbit_provider_created'              => 'App\\Orbit\\OrbitProvider를 생성했습니다.',
                'user_model_creating'                 => 'User 모델이 없어 Orbit 기본 User 스텁을 생성합니다.',
                'user_model_overwrite_warning'        => 'app/Models/User.php 파일이 이미 있습니다. Orbit 설치는 이 파일을 Orbit 기본 User 모델(extends OrbitUser)로 덮어씁니다. 기존 커스텀 코드는 사라집니다.',
                'user_model_overwrite_confirm'        => 'User 모델을 Orbit 기본 스텁으로 덮어쓸까요?',
                'user_model_overwriting'              => 'User 모델을 Orbit 기본 스텁으로 교체합니다.',
                'user_model_kept'                     => '기존 User 모델을 유지합니다.',
                'user_model_not_found'                => 'app/Models/User.php 를 찾을 수 없습니다. 아래 호환 안내를 참고해 수동으로 구성해 주세요.',
                'user_model_compat_title'             => '기존 User 모델 호환 안내',
                'user_model_compat_intro'             => 'Orbit 기능을 사용하려면 애플리케이션 User 모델에 아래 내용을 반영해 주세요.',
                'user_model_compat_option_extend'     => '방법 A (권장): CmsOrbit\\Core\\Foundation\\Models\\User 를 extends 하세요.',
                'user_model_compat_option_traits'     => '방법 B: 기존 Authenticatable User를 유지하고 아래 trait을 추가하세요.',
                'user_model_compat_trait_user_access' => '  • CmsOrbit\\Core\\Access\\UserAccess',
                'user_model_compat_trait_accounts'    => '  • CmsOrbit\\Core\\Auth\\Concerns\\HasOrbitUserAccounts',
                'user_model_compat_use_model'         => 'App\\Orbit\\OrbitProvider::boot() 에 모델 치환을 등록하세요:',
                'user_model_compat_use_model_code'    => '  Orbit::useModel(CmsOrbit\\Core\\Foundation\\Models\\User::class, App\\Models\\User::class);',
                'user_model_compat_auth'              => 'config/auth.php 의 users provider model 이 App\\Models\\User 를 가리키는지 확인하세요.',
                'user_model_compat_casts'             => 'permissions(array), must_change_password(bool) 캐스트와 fillable/hidden 속성이 Orbit 마이그레이션과 맞는지 확인하세요.',
                'command_failed'                      => '오류가 발생했습니다. \':command :parameters\' 명령이 실행되지 않았습니다.',
                'completed'                           => '설치가 완료되었습니다!',
                'create_admin_hint'                   => "관리자 계정을 만들려면 'php artisan orbit:admin' 을 실행하세요.",
                'serve_hint'                          => "개발 서버를 시작하려면 'php artisan serve' 를 실행하세요.",
                'show_love_confirm'                   => 'GitHub에 ⭐ 를 남겨 응원해 주시겠어요?',
                'show_love_thanks'                    => '감사합니다! 큰 힘이 됩니다!',
                'show_love_link'                      => '저장소: :url',
            ],
            InstallLocale::English->value => [
                'started'                             => 'Orbit installation started. Please wait...',
                'version'                             => 'Version: :version',
                'step_publish'                        => 'Publishing config, migrations, stubs, and assets...',
                'step_migrate'                        => 'Running database migrations...',
                'step_storage_link'                   => 'Creating the storage symbolic link...',
                'step_user_model'                     => 'Configuring the User model...',
                'step_namespace_replace'              => 'Normalizing User namespace references in app code...',
                'step_entities'                       => 'Preparing the entities directory...',
                'step_orbit_provider'                 => 'Preparing OrbitProvider...',
                'step_frontend_sync'                  => 'Syncing Inertia/Vite scaffolding for installed Orbit packages...',
                'step_ai'                             => 'Publishing AI agent skills...',
                'entities_created'                    => 'Created the root /entities directory.',
                'orbit_provider_created'              => 'Created App\\Orbit\\OrbitProvider.',
                'user_model_creating'                 => 'User model not found. Creating the Orbit default User stub.',
                'user_model_overwrite_warning'        => 'app/Models/User.php already exists. Orbit install will replace it with the default Orbit User model (extends OrbitUser). Any custom code in that file will be lost.',
                'user_model_overwrite_confirm'        => 'Overwrite the User model with the Orbit default stub?',
                'user_model_overwriting'              => 'Replacing the User model with the Orbit default stub.',
                'user_model_kept'                     => 'Keeping your existing User model.',
                'user_model_not_found'                => 'Unable to locate app/Models/User.php. Follow the compatibility guide below to configure it manually.',
                'user_model_compat_title'             => 'Existing User model compatibility guide',
                'user_model_compat_intro'             => 'To use Orbit features with your application User model, apply the following:',
                'user_model_compat_option_extend'     => 'Option A (recommended): extend CmsOrbit\\Core\\Foundation\\Models\\User.',
                'user_model_compat_option_traits'     => 'Option B: keep your Authenticatable User and add these traits:',
                'user_model_compat_trait_user_access' => '  • CmsOrbit\\Core\\Access\\UserAccess',
                'user_model_compat_trait_accounts'    => '  • CmsOrbit\\Core\\Auth\\Concerns\\HasOrbitUserAccounts',
                'user_model_compat_use_model'         => 'Register model replacement in App\\Orbit\\OrbitProvider::boot():',
                'user_model_compat_use_model_code'    => '  Orbit::useModel(CmsOrbit\\Core\\Foundation\\Models\\User::class, App\\Models\\User::class);',
                'user_model_compat_auth'              => 'Ensure config/auth.php users provider model points to App\\Models\\User.',
                'user_model_compat_casts'             => 'Ensure permissions (array), must_change_password (bool) casts and fillable/hidden attributes match Orbit migrations.',
                'command_failed'                      => "An error has occurred. The ':command :parameters' command was not executed.",
                'completed'                           => 'Installation completed!',
                'create_admin_hint'                   => "To create an admin user, run 'php artisan orbit:admin'.",
                'serve_hint'                          => "To start the development server, run 'php artisan serve'.",
                'show_love_confirm'                   => 'Would you like to show a little love by starring us on GitHub?',
                'show_love_thanks'                    => 'Thank you! It means a lot to us!',
                'show_love_link'                      => 'Repository: :url',
            ],
        ];
    }
}
