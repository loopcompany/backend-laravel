<?php

namespace Tests\Feature;

use App\Models\OrganizationTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTermApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_organization_terms_list()
    {
        // Arrange: Create some organization terms
        OrganizationTerm::factory()->count(3)->create();

        // Act: Call the API
        $response = $this->getJson('/api/info/organization-terms');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJson([
                'status' => 'success'
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_empty_array_when_no_organization_terms_exist()
    {
        // Act: Call the API without creating any terms
        $response = $this->getJson('/api/info/organization-terms');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => []
            ]);
    }

    /** @test */
    public function organization_terms_api_does_not_require_authentication()
    {
        // Arrange: Create organization terms
        OrganizationTerm::factory()->create();

        // Act: Call API without authentication
        $response = $this->getJson('/api/info/organization-terms');

        // Assert: Should succeed without auth
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    /** @test */
    public function organization_terms_response_includes_all_fields()
    {
        // Arrange
        $term = OrganizationTerm::factory()->create([
            'title' => 'قوانین عمومی سازمان',
            'description' => '<p>این متن توضیحات قوانین سازمانی است</p>'
        ]);

        // Act
        $response = $this->getJson('/api/info/organization-terms');

        // Assert
        $response->assertStatus(200);
        
        $data = $response->json('data.0');
        $this->assertEquals($term->id, $data['id']);
        $this->assertEquals('قوانین عمومی سازمان', $data['title']);
        $this->assertEquals('<p>این متن توضیحات قوانین سازمانی است</p>', $data['description']);
        $this->assertNotNull($data['created_at']);
        $this->assertNotNull($data['updated_at']);
    }
}
