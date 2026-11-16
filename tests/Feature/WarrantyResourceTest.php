<?php
namespace Tests\Feature;
use App\Models\{Warranty,WarrantyCategory};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class WarrantyResourceTest extends TestCase
{
    use RefreshDatabase;
    public function test_public_api_returns_warranties_nested_under_their_category(): void {
        $category=WarrantyCategory::create(['title'=>'Warranty category']);
        Warranty::factory()->create(['title'=>'Original goods','description'=>'First warranty','warranty_category_id'=>$category->id]);
        Warranty::factory()->create(['title'=>'Return','description'=>'Second warranty','warranty_category_id'=>$category->id]);
        $this->getJson('/api/info/warranties')->assertOk()->assertJsonCount(1,'data')->assertJsonCount(2,'data.0.warranties')->assertJsonFragment(['title'=>'Original goods'])->assertJsonFragment(['title'=>'Return']);
    }
    public function test_empty_categories_are_returned_without_fabricated_warranties(): void {
        WarrantyCategory::create(['title'=>'Empty']);
        $this->getJson('/api/info/warranties')->assertOk()->assertJsonCount(0,'data.0.warranties');
    }
}
