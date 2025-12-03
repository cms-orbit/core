<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

use InvalidArgumentException;

/**
 * Package Path Resolver
 * 
 * 커맨드에서 --package 옵션을 처리하여 적절한 경로와 네임스페이스를 해석합니다.
 */
class PackagePathResolver
{
    /**
     * 패키지 경로 해석
     *
     * @param string|null $package vendor/package 형식 또는 null (기본: 사용자 프로젝트)
     * @return array{base_path: string, namespace: string, is_package: bool, package_name: string|null}
     * @throws InvalidArgumentException
     */
    public static function resolve(?string $package = null): array
    {
        if (!$package) {
            // 기본: 사용자 프로젝트의 app
            return [
                'base_path' => app_path(),
                'namespace' => rtrim(app()->getNamespace(), '\\'),
                'is_package' => false,
                'package_name' => null,
            ];
        }

        // 외부 패키지 경로 해석
        return static::resolvePackage($package);
    }

    /**
     * 외부 패키지 경로 해석
     *
     * @param string $package vendor/package 형식
     * @return array{base_path: string, namespace: string, is_package: bool, package_name: string}
     * @throws InvalidArgumentException
     */
    protected static function resolvePackage(string $package): array
    {
        $vendorPath = base_path("vendor/{$package}");

        if (!is_dir($vendorPath)) {
            throw new InvalidArgumentException("Package '{$package}' not found in vendor directory");
        }

        $composerJsonPath = "{$vendorPath}/composer.json";
        
        if (!file_exists($composerJsonPath)) {
            throw new InvalidArgumentException("composer.json not found in package '{$package}'");
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        if (!$composerJson) {
            throw new InvalidArgumentException("Invalid composer.json in package '{$package}'");
        }

        // PSR-4 autoload에서 첫 번째 네임스페이스 추출
        $autoload = $composerJson['autoload']['psr-4'] ?? [];

        if (empty($autoload)) {
            throw new InvalidArgumentException("No PSR-4 autoload defined in package '{$package}'");
        }

        // 첫 번째 네임스페이스와 경로 사용
        $namespace = array_key_first($autoload);
        $srcPath = $autoload[$namespace];

        // 네임스페이스에서 trailing backslash 제거
        $namespace = rtrim($namespace, '\\');

        // 경로 정규화
        $basePath = rtrim("{$vendorPath}/{$srcPath}", '/');

        return [
            'base_path' => $basePath,
            'namespace' => $namespace,
            'is_package' => true,
            'package_name' => $package,
        ];
    }

    /**
     * 엔티티 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function entityPath(?string $package, string $entityName): string
    {
        $resolved = static::resolve($package);
        return "{$resolved['base_path']}/Entities/{$entityName}";
    }

    /**
     * 엔티티 네임스페이스 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function entityNamespace(?string $package, string $entityName): string
    {
        $resolved = static::resolve($package);
        return "{$resolved['namespace']}\\Entities\\{$entityName}";
    }

    /**
     * 모델 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @param string $modelName
     * @return string
     */
    public static function modelPath(?string $package, string $entityName, string $modelName): string
    {
        $entityPath = static::entityPath($package, $entityName);
        return "{$entityPath}/{$modelName}.php";
    }

    /**
     * 모델 네임스페이스 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function modelNamespace(?string $package, string $entityName): string
    {
        return static::entityNamespace($package, $entityName);
    }

    /**
     * Screen 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @param string $screenName
     * @return string
     */
    public static function screenPath(?string $package, string $entityName, string $screenName): string
    {
        $entityPath = static::entityPath($package, $entityName);
        return "{$entityPath}/Screens/{$screenName}.php";
    }

    /**
     * Screen 네임스페이스 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function screenNamespace(?string $package, string $entityName): string
    {
        $entityNamespace = static::entityNamespace($package, $entityName);
        return "{$entityNamespace}\\Screens";
    }

    /**
     * Layout 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @param string $layoutName
     * @return string
     */
    public static function layoutPath(?string $package, string $entityName, string $layoutName): string
    {
        $entityPath = static::entityPath($package, $entityName);
        return "{$entityPath}/Layouts/{$layoutName}.php";
    }

    /**
     * Layout 네임스페이스 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function layoutNamespace(?string $package, string $entityName): string
    {
        $entityNamespace = static::entityNamespace($package, $entityName);
        return "{$entityNamespace}\\Layouts";
    }

    /**
     * Presenter 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @param string $presenterName
     * @return string
     */
    public static function presenterPath(?string $package, string $entityName, string $presenterName): string
    {
        $entityPath = static::entityPath($package, $entityName);
        return "{$entityPath}/Presenters/{$presenterName}.php";
    }

    /**
     * Presenter 네임스페이스 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function presenterNamespace(?string $package, string $entityName): string
    {
        $entityNamespace = static::entityNamespace($package, $entityName);
        return "{$entityNamespace}\\Presenters";
    }

    /**
     * Routes 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function routesPath(?string $package, string $entityName): string
    {
        $entityPath = static::entityPath($package, $entityName);
        return "{$entityPath}/routes/orbit.php";
    }

    /**
     * Factory 경로 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @param string $factoryName
     * @return string
     */
    public static function factoryPath(?string $package, string $entityName, string $factoryName): string
    {
        $entityPath = static::entityPath($package, $entityName);
        return "{$entityPath}/Factories/{$factoryName}.php";
    }

    /**
     * Factory 네임스페이스 생성
     *
     * @param string|null $package
     * @param string $entityName
     * @return string
     */
    public static function factoryNamespace(?string $package, string $entityName): string
    {
        $entityNamespace = static::entityNamespace($package, $entityName);
        return "{$entityNamespace}\\Factories";
    }

    /**
     * 디렉터리 생성
     *
     * @param string $path
     * @return void
     */
    public static function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * 파일이 존재하는지 확인
     *
     * @param string $path
     * @return bool
     */
    public static function fileExists(string $path): bool
    {
        return file_exists($path);
    }
}

