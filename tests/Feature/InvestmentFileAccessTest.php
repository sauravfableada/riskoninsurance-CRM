<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class InvestmentFileAccessTest extends TestCase
{
    /**
     * Test that files stored on public disk are accessible via the uploads route.
     */
    public function test_uploads_investments_route_serves_files_from_storage()
    {
        Storage::fake('public');

        // Put a dummy file in fake public storage
        Storage::disk('public')->put('investments/test_document.txt', 'This is dummy document content.');

        // Request the route
        $response = $this->get('/uploads/investments/test_document.txt');

        // Assert response status and content
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals(
            Storage::disk('public')->path('investments/test_document.txt'),
            $response->getFile()->getPathname()
        );
    }

    /**
     * Test that nested files (like extra documents) are also accessible.
     */
    public function test_uploads_investments_route_serves_nested_files_from_storage()
    {
        Storage::fake('public');

        // Put a dummy file in fake public storage under investments/extra
        Storage::disk('public')->put('investments/extra/test_extra.txt', 'Extra document content.');

        // Request the route
        $response = $this->get('/uploads/investments/extra/test_extra.txt');

        // Assert response status and content
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals(
            Storage::disk('public')->path('investments/extra/test_extra.txt'),
            $response->getFile()->getPathname()
        );
    }

    /**
     * Test that missing files return a 404.
     */
    public function test_missing_files_return_404()
    {
        Storage::fake('public');

        $response = $this->get('/uploads/investments/does_not_exist.txt');

        $response->assertStatus(404);
    }
}
