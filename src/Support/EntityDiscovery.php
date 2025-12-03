<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Entity Discovery Service
 * 
 * 앱과 패키지의 엔티티를 자동으로 발견하고 등록합니다.
 */
class EntityDiscovery
{
    protected Collection $entityPaths;
    protected Collection $discoveredEntities;

    public function __construct()
    {
        $this->entityPaths = new Collection();
        $this->discoveredEntities = new Collection();
    }

    /**
     * 엔티티 경로 등록
     */
    public function registerPath(string $path): void
    {
        if (is_dir($path)) {
            $this->entityPaths->push($path);
        }
    }

    /**
     * 모든 엔티티 발견
     */
    public function discover(): Collection
    {
        if ($this->discoveredEntities->isNotEmpty()) {
            return $this->discoveredEntities;
        }

        foreach ($this->entityPaths as $path) {
            $this->discoverInPath($path);
        }

        return $this->discoveredEntities;
    }

    /**
     * 특정 경로에서 엔티티 발견
     */
    protected function discoverInPath(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && $this->isEntityDirectory($file->getPathname())) {
                $this->registerEntity($file->getPathname());
            }
        }
    }

    /**
     * 엔티티 디렉터리인지 확인
     */
    protected function isEntityDirectory(string $path): bool
    {
        // Screens 또는 Layouts 디렉터리가 있으면 엔티티로 간주
        return is_dir($path . '/Screens') || is_dir($path . '/Layouts');
    }

    /**
     * 엔티티 등록
     */
    protected function registerEntity(string $path): void
    {
        $entityName = basename($path);
        $modelPath = $path . '/' . $entityName . '.php';

        if (!file_exists($modelPath)) {
            return;
        }

        // 모델 클래스 찾기
        $modelClass = $this->getModelClass($modelPath);

        if (!$modelClass) {
            return;
        }

        // Check if entity uses Resource or Screen
        $resourcePath = $path . '/' . $entityName . 'Resource.php';
        $isResource = file_exists($resourcePath);

        // Check if model uses SoftDeletes
        $hasSoftDeletes = $this->hasSoftDeletes($modelPath);

        $this->discoveredEntities->push([
            'name' => $entityName,
            'path' => $path,
            'model' => $modelClass,
            'routes' => $path . '/routes/orbit.php',
            'migrations' => $path . '/database/migrations',
            'is_resource' => $isResource,
            'has_soft_deletes' => $hasSoftDeletes,
        ]);
    }

    /**
     * 모델 클래스 이름 추출
     */
    protected function getModelClass(string $modelPath): ?string
    {
        $content = file_get_contents($modelPath);

        // namespace 추출
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
            $className = basename($modelPath, '.php');
            $fullClass = $namespace . '\\' . $className;

            if (class_exists($fullClass)) {
                return $fullClass;
            }
        }

        return null;
    }

    /**
     * Check if model uses SoftDeletes
     */
    protected function hasSoftDeletes(string $modelPath): bool
    {
        $content = file_get_contents($modelPath);
        
        // Check for SoftDeletes trait
        return str_contains($content, 'use SoftDeletes') || 
               str_contains($content, 'use Illuminate\Database\Eloquent\SoftDeletes');
    }

    /**
     * 엔티티의 라우트 파일 목록
     */
    public function getRouteFiles(): Collection
    {
        return $this->discover()
            ->pluck('routes')
            ->filter(fn ($route) => file_exists($route));
    }

    /**
     * 엔티티의 마이그레이션 경로 목록
     */
    public function getMigrationPaths(): Collection
    {
        return $this->discover()
            ->pluck('migrations')
            ->filter(fn ($path) => is_dir($path));
    }

    /**
     * 메뉴 생성을 위한 엔티티 목록
     */
    public function getMenuItems(): Collection
    {
        return $this->discover()->map(function ($entity) {
            $modelClass = $entity['model'];
            
            // HasPermissions trait를 사용하는지 확인
            if (!method_exists($modelClass, 'getPermissions')) {
                return null;
            }

            try {
                $reflection = new ReflectionClass($modelClass);
                $instance = $reflection->newInstanceWithoutConstructor();
                
                $table = $instance->getTable();
                $routeName = Str::snake(Str::plural($entity['name']));

                return [
                    'name' => $entity['name'],
                    'title' => Str::title(Str::replace('_', ' ', $table)),
                    'route' => "orbit.{$routeName}.list",
                    'icon' => 'bs.list',
                    'permission' => "orbit.entities.{$table}",
                    'sort' => 1000,
                ];
            } catch (\Exception $e) {
                return null;
            }
        })->filter();
    }
}

