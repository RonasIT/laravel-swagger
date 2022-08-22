<?php

namespace RonasIT\Tests\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use function app;
use function array_concat;
use function config;
use function env;

trait FixturesTrait
{
    protected static $tables;
    protected $postgisTables = [
        'tiger.addrfeat',
        'tiger.edges',
        'tiger.faces',
        'topology.topology',
        'tiger.place_lookup',
        'topology.layer',
        'tiger.geocode_settings',
        'tiger.geocode_settings_default',
        'tiger.direction_lookup',
        'tiger.secondary_unit_lookup',
        'tiger.state_lookup',
        'tiger.street_type_lookup',
        'tiger.county_lookup',
        'tiger.countysub_lookup',
        'tiger.zip_lookup_all',
        'tiger.zip_lookup_base',
        'tiger.zip_lookup',
        'tiger.county',
        'tiger.state',
        'tiger.place',
        'tiger.zip_state',
        'tiger.zip_state_loc',
        'tiger.cousub',
        'tiger.featnames',
        'tiger.addr',
        'tiger.zcta5',
        'tiger.loader_platform',
        'tiger.loader_variables',
        'tiger.loader_lookuptables',
        'tiger.tract',
        'tiger.tabblock',
        'tiger.bg',
        'tiger.pagc_gaz',
        'tiger.pagc_lex',
        'tiger.pagc_rules',
    ];

    protected $truncateExceptTables = ['migrations', 'password_resets'];
    protected $prepareSequencesExceptTables = ['migrations', 'password_resets'];

    protected function loadTestDump(): void
    {
        $dump = $this->getFixture('dump.sql', false);

        if (empty($dump)) {
            return;
        }

        $databaseTables = $this->getTables();
        $scheme = config('database.default');

        $this->clearDatabase($scheme, $databaseTables, array_merge($this->postgisTables, $this->truncateExceptTables));

        DB::unprepared($dump);
    }

    public function getFixturePath(string $fixtureName): string
    {
        $class = get_class($this);
        $explodedClass = explode('\\', $class);
        $className = Arr::last($explodedClass);

        return __DIR__."/../fixtures/{$className}/{$fixtureName}";
    }

    public function getFixture(string $fixtureName, $failIfNotExists = true): string
    {
        $path = $this->getFixturePath($fixtureName);

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        if ($failIfNotExists) {
            $this->fail($path . ' fixture does not exist');
        }

        return '';
    }

    public function getJsonFixture(string $fixtureName, $assoc = true)
    {
        return json_decode($this->getFixture($fixtureName), $assoc);
    }

    public function assertEqualsFixture(string $fixture, $data, bool $exportMode = false): void
    {
        if ($exportMode || $this->globalExportMode) {
            $this->exportJson($fixture, $data);
        }

        $this->assertEquals($this->getJsonFixture($fixture), $data);
    }

    public function callRawRequest(string $method, string $uri, $content, array $headers = []): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers);

        return $this->call($method, $uri, [], [], [], $server, $content);
    }

    public function exportJson($fixture, $data): void
    {
        if ($data instanceof TestResponse) {
            $data = $data->json();
        }

        $this->exportContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $fixture);
    }

    public function clearDatabase(string $scheme, array $tables, array $except): void
    {
        if ($scheme === 'pgsql') {
            $query = $this->getClearPsqlDatabaseQuery($tables, $except);
        } elseif ($scheme === 'mysql') {
            $query = $this->getClearMySQLDatabaseQuery($tables, $except);
        }

        if (!empty($query)) {
            app('db.connection')->unprepared($query);
        }
    }

    public function getClearPsqlDatabaseQuery(array $tables, array $except = ['migrations']): string
    {
        return array_concat($tables, function ($table) use ($except) {
            if (in_array($table, $except)) {
                return '';
            } else {
                return "TRUNCATE {$table} RESTART IDENTITY CASCADE; \n";
            }
        });
    }

    public function getClearMySQLDatabaseQuery(array $tables, array $except = ['migrations']): string
    {
        $query = "SET FOREIGN_KEY_CHECKS = 0;\n";

        $query .= array_concat($tables, function ($table) use ($except) {
            if (in_array($table, $except)) {
                return '';
            } else {
                return "TRUNCATE TABLE {$table}; \n";
            }
        });

        return "{$query} SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    public function prepareSequences(array $tables, array $except = []): void
    {
        $except = array_merge($this->postgisTables, $this->prepareSequencesExceptTables, $except);

        $query = array_concat($tables, function ($table) use ($except) {
            if (in_array($table, $except)) {
                return '';
            } else {
                return "SELECT setval('{$table}_id_seq', (select max(id) from {$table}));\n";
            }
        });

        app('db.connection')->unprepared($query);
    }

    public function exportFile(TestResponse $response, string $fixture): void
    {
        $this->exportContent(
            file_get_contents($response->getFile()->getPathName()),
            $fixture
        );
    }

    protected function getTables(): array
    {
        if (empty(self::$tables)) {
            self::$tables = app('db.connection')
                ->getDoctrineSchemaManager()
                ->listTableNames();
        }

        return self::$tables;
    }

    protected function exportContent($content, string $fixture): void
    {
        if (env('FAIL_EXPORT_JSON', true)) {
            $this->fail(preg_replace('/[ ]+/mu', ' ',
                'Looks like you forget to remove exportJson. If it is your local environment add 
                FAIL_EXPORT_JSON=false to .env.testing.
                If it is dev.testing environment then remove it.'
            ));
        }

        file_put_contents(
            $this->getFixturePath($fixture),
            $content
        );
    }
}
