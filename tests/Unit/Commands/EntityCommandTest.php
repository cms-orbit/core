<?php

namespace CmsOrbit\Core\Tests\Unit\Commands;

use CmsOrbit\Core\Commands\EntityCommand;
use CmsOrbit\Core\Tests\TestCase;
use Illuminate\Support\Facades\File;

class EntityCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up created files
        if (File::exists(app_path('Orbit'))) {
            File::deleteDirectory(app_path('Orbit'));
        }

        parent::tearDown();
    }

    /** @test */
    public function it_creates_entity_in_default_location(): void
    {
        $this->artisan('cms:entity', ['name' => 'Product', '--no-interaction' => true])
            ->assertExitCode(0);

        // Check model
        $this->assertFileExists(app_path('Orbit/Entities/Product/Product.php'));
        
        // Check screens
        $this->assertFileExists(app_path('Orbit/Entities/Product/Screens/ProductListScreen.php'));
        $this->assertFileExists(app_path('Orbit/Entities/Product/Screens/ProductEditScreen.php'));
        
        // Check layouts
        $this->assertFileExists(app_path('Orbit/Entities/Product/Layouts/ProductListLayout.php'));
        $this->assertFileExists(app_path('Orbit/Entities/Product/Layouts/ProductEditLayout.php'));
        
        // Check routes
        $this->assertFileExists(app_path('Orbit/Entities/Product/routes/orbit.php'));
    }

    /** @test */
    public function it_generates_correct_namespace_in_model(): void
    {
        $this->artisan('cms:entity', ['name' => 'Product', '--no-interaction' => true])
            ->assertExitCode(0);

        $content = File::get(app_path('Orbit/Entities/Product/Product.php'));
        
        $this->assertStringContainsString('namespace App\Orbit\Entities\Product;', $content);
        $this->assertStringContainsString('class Product extends DynamicModel', $content);
        $this->assertStringContainsString('use HasPermissions;', $content);
    }

    /** @test */
    public function it_generates_correct_table_name(): void
    {
        $this->artisan('cms:entity', ['name' => 'Product', '--no-interaction' => true])
            ->assertExitCode(0);

        $content = File::get(app_path('Orbit/Entities/Product/Product.php'));
        
        $this->assertStringContainsString("protected \$table = 'products';", $content);
    }

    /** @test */
    public function it_generates_screens_with_correct_namespaces(): void
    {
        $this->artisan('cms:entity', ['name' => 'Product', '--no-interaction' => true])
            ->assertExitCode(0);

        $listScreen = File::get(app_path('Orbit/Entities/Product/Screens/ProductListScreen.php'));
        $editScreen = File::get(app_path('Orbit/Entities/Product/Screens/ProductEditScreen.php'));
        
        $this->assertStringContainsString('namespace App\Orbit\Entities\Product\Screens;', $listScreen);
        $this->assertStringContainsString('class ProductListScreen extends Screen', $listScreen);
        
        $this->assertStringContainsString('namespace App\Orbit\Entities\Product\Screens;', $editScreen);
        $this->assertStringContainsString('class ProductEditScreen extends Screen', $editScreen);
    }

    /** @test */
    public function it_generates_layouts_with_correct_namespaces(): void
    {
        $this->artisan('cms:entity', ['name' => 'Product', '--no-interaction' => true])
            ->assertExitCode(0);

        $listLayout = File::get(app_path('Orbit/Entities/Product/Layouts/ProductListLayout.php'));
        $editLayout = File::get(app_path('Orbit/Entities/Product/Layouts/ProductEditLayout.php'));
        
        $this->assertStringContainsString('namespace App\Orbit\Entities\Product\Layouts;', $listLayout);
        $this->assertStringContainsString('class ProductListLayout extends Table', $listLayout);
        
        $this->assertStringContainsString('namespace App\Orbit\Entities\Product\Layouts;', $editLayout);
        $this->assertStringContainsString('class ProductEditLayout extends Rows', $editLayout);
    }

    /** @test */
    public function it_generates_routes_with_correct_paths(): void
    {
        $this->artisan('cms:entity', ['name' => 'Product', '--no-interaction' => true])
            ->assertExitCode(0);

        $routes = File::get(app_path('Orbit/Entities/Product/routes/orbit.php'));
        
        $this->assertStringContainsString("Route::screen('entities/products'", $routes);
        $this->assertStringContainsString("->name('orbit.entities.products')", $routes);
        $this->assertStringContainsString("Route::screen('entities/products/create'", $routes);
        $this->assertStringContainsString("Route::screen('entities/products/{product}'", $routes);
    }

    /** @test */
    public function it_handles_multi_word_entity_names(): void
    {
        $this->artisan('cms:entity', ['name' => 'ProductCategory', '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Orbit/Entities/ProductCategory/ProductCategory.php'));
        
        $content = File::get(app_path('Orbit/Entities/ProductCategory/ProductCategory.php'));
        $this->assertStringContainsString("protected \$table = 'product_categories';", $content);
    }
}

