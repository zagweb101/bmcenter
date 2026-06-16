<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * توليد وثيقة OpenAPI يعمل ويغطّي المسارات الأساسية. PRD §21, §29 (DoD).
 */
class OpenApiTest extends TestCase
{
    public function test_openapi_document_generates_and_covers_core_endpoints(): void
    {
        $path = storage_path('app/openapi-test.json');
        @unlink($path);

        Artisan::call('scramble:export', ['--path' => $path]);

        $this->assertFileExists($path);
        $doc = json_decode(file_get_contents($path), true);
        @unlink($path);

        $this->assertSame('3.1.0', $doc['openapi'] ?? null);

        $paths = array_keys($doc['paths'] ?? []);
        $this->assertContains('/v1/auth/login', $paths);
        $this->assertContains('/v1/persons', $paths);
        $this->assertContains('/v1/privacy-requests', $paths);
        $this->assertGreaterThanOrEqual(10, count($paths));
    }
}
