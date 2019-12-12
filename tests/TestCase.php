<?php

namespace Boomdraw\Canonicalizable\Tests;

use Boomdraw\Canonicalizable\Tests\Integration\TestModel;
use Boomdraw\Canonicalizer\CanonicalizerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use File;

class TestCase extends BaseTestCase
{
    /** @var TestModel */
    protected TestModel $testModel;

    /** @var string */
    protected $email = 'HelLo.World@HellO.cOM.Nl';

    /** @var string */
    protected $other_email = 'BlAblABla@GmaIl.cOm';

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory() . '/database.sqlite',
            'prefix' => '',
        ]);
    }

    /**
     * Load package service provider.
     *
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            CanonicalizerServiceProvider::class,
        ];
    }

    /**
     * Set up database.
     *
     * @param $app
     * @return void
     */
    protected function setUpDatabase(Application $app): void
    {
        file_put_contents($this->getTempDirectory() . '/database.sqlite', null);
        $app['db']->connection()->getSchemaBuilder()->create('test_models', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('other_field')->nullable();
            $table->string('email_canonical')->nullable();
        });
        $app['db']->connection()->getSchemaBuilder()->create('test_model_soft_deletes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('other_field')->nullable();
            $table->string('email_canonical')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Initializes specified directory.
     *
     * @param string $directory
     * @return void
     */
    protected function initializeDirectory(string $directory): void
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory);
        }
    }

    /**
     * Returns temp directory.
     *
     * @return string
     */
    public function getTempDirectory(): string
    {
        return __DIR__ . '/tmp';
    }
}
