<?php

namespace Tests;

use Artisan;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UKFast\HealthCheck\Checks\LogHealthCheck;
use UKFast\HealthCheck\Commands\UpdateSchedulerTimestamp;
use UKFast\HealthCheck\HealthCheckServiceProvider;
use URL;

class HealthCheckServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function configures_healthcheck_package()
    {
        $this->app->register(HealthCheckServiceProvider::class);

        $this->assertNotNull(config('healthcheck'));
    }

    /**
     * @test
     */
    public function registers_health_check_route()
    {
        $this->app->register(HealthCheckServiceProvider::class);

        config(['healthcheck.checks' => []]);

        $response = $this->get('/health');
        $this->assertSame('{"status":"OK"}', $response->getContent());
    }

    /**
     * @test
     */
    public function registers_ping_route()
    {
        $this->app->register(HealthCheckServiceProvider::class);

        config(['healthcheck.checks' => []]);

        $response = $this->get('/ping');
        $this->assertSame('pong', $response->getContent());
    }

    /**
     * @test
     */
    public function registers_scheduler_health_check_command()
    {
        $this->app->register(HealthCheckServiceProvider::class);

        $this->assertArrayHasKey('health-check:cache-scheduler-running', Artisan::all());
    }

    /**
     * @test
     */
    public function binds_app_health()
    {
        $this->app->register(HealthCheckServiceProvider::class);

        config(['healthcheck.checks' => [\UKFast\HealthCheck\Checks\EnvHealthCheck::class]]);

        $this->assertInstanceOf(\UKFast\HealthCheck\AppHealth::class, $this->app->make('app-health'));
    }

    /**
     * @test
     */
    public function uses_base_path_for_health_check_routes()
    {
        config(['healthcheck.base-path' => '/test/']);
        $this->app->register(HealthCheckServiceProvider::class);

        $routes = $this->app->make('router')->getRoutes();

        $this->assertNotNull($routes->match(Request::create('/test/ping')));
        $this->expectException(NotFoundHttpException::class);
        $this->assertNull($routes->match(Request::create('/ping')));

        $this->assertNotNull($routes->match(Request::create('/test/health')));
        $this->expectException(NotFoundHttpException::class);
        $this->assertNull($routes->match(Request::create('/health')));
    }

    /**
     * @test
     */
    public function base_path_defaults_to_nothing()
    {
        config(['healthcheck.base-path' => '']);
        $this->app->register(HealthCheckServiceProvider::class);

        $routes = $this->app->make('router')->getRoutes();

        $this->assertNotNull($routes->match(Request::create('/ping')));
        $this->assertNotNull($routes->match(Request::create('/health')));
    }

    /**
     * @test
     */
    public function registered_route_has_a_name()
    {
        $this->app->register(HealthCheckServiceProvider::class);
        $routes = $this->app->make('router')->getRoutes();
        $this->assertEquals(config('healthcheck.routename'), $routes->match(Request::create('/health'))->getName());
    }

    /**
     * @test
     */
    public function health_name_can_be_used_for_route_generation()
    {
        $this->app->register(HealthCheckServiceProvider::class);

        $mehodExists = is_callable(URL::class, 'signedRoute');

        if (! $mehodExists) {
            $this->markTestSkipped('URL::signedRoute does not exists');

            return;
        }

        $url = URL::signedRoute(config('healthcheck.routename'));
        $this->assertNotNull($url);
    }
}
