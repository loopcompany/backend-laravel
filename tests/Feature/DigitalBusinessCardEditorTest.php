<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DigitalBusinessCard;
use App\Models\DigitalBusinessCardBlock;
use App\Models\FaqBlock;
use App\Models\FaqItem;
use App\Models\GalleryBlock;
use App\Models\GalleryItem;
use App\Models\TextBlock;
use App\Support\DigitalBusinessCardTypography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DigitalBusinessCardEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'create-digital-business-cards', 'guard_name' => 'admin']);
        Permission::create(['name' => 'view-digital-business-cards', 'guard_name' => 'admin']);
        Permission::create(['name' => 'edit-digital-business-cards', 'guard_name' => 'admin']);
    }

    private function createAdmin(array $permissions = []): Admin
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        if (! empty($permissions)) {
            $admin->givePermissionTo($permissions);
        }

        return $admin;
    }

    public function test_editor_data_returns_normalized_payload(): void
    {
        $admin = $this->createAdmin(['view-digital-business-cards']);
        $card = DigitalBusinessCard::create([
            'title' => 'My Card',
            'des' => 'Description',
            'logo' => '/uploads/logo.png',
            'image_background' => '/uploads/bg.jpg',
            'page_background_image' => '/uploads/page-bg.jpg',
            'header_text_color' => '#11aa33',
        ]);

        $textBlock = DigitalBusinessCardBlock::create([
            'digital_business_card_id' => $card->id,
            'type' => DigitalBusinessCardBlock::TYPE_TEXT,
            'title' => 'Intro',
            'sort' => 1,
            'color' => '#ffffff',
            'background_color' => '#fef3c7',
        ]);

        TextBlock::create([
            'digital_business_card_id' => $card->id,
            'digital_business_card_block_id' => $textBlock->id,
            'descriptions' => DigitalBusinessCardTypography::embedTypographyInText('Hello world', [
                'title_font' => 'vazir',
                'description_font' => 'iransans',
            ]),
            'sort' => 1,
            'color' => '#ffffff',
        ]);

        $faqBlock = DigitalBusinessCardBlock::create([
            'digital_business_card_id' => $card->id,
            'type' => DigitalBusinessCardBlock::TYPE_FAQ,
            'title' => 'FAQ',
            'sort' => 2,
            'color' => '#f4f4f4',
            'background_color' => '#ecfeff',
        ]);

        $faqContainer = FaqBlock::create([
            'digital_business_card_id' => $card->id,
            'digital_business_card_block_id' => $faqBlock->id,
            'title' => 'FAQ',
            'descriptions' => DigitalBusinessCardTypography::encodeTypography([
                'faq_question_font' => 'yekan',
                'faq_answer_font' => 'tanha',
            ]),
            'sort' => 2,
            'title_color' => '#f4f4f4',
            'descriptions_color' => '#f4f4f4',
        ]);

        FaqItem::create([
            'faq_block_id' => $faqContainer->id,
            'question' => 'Working hours?',
            'answer' => '9 to 5',
            'sort' => 1,
        ]);

        $galleryBlock = DigitalBusinessCardBlock::create([
            'digital_business_card_id' => $card->id,
            'type' => DigitalBusinessCardBlock::TYPE_GALLERY,
            'title' => 'Portfolio',
            'sort' => 3,
            'color' => '#ffffff',
            'background_color' => '#f5f3ff',
        ]);

        $galleryContainer = GalleryBlock::create([
            'digital_business_card_id' => $card->id,
            'digital_business_card_block_id' => $galleryBlock->id,
            'title' => 'Portfolio',
            'descriptions' => DigitalBusinessCardTypography::encodeTypography([
                'caption_font' => 'vazir',
            ]),
            'file_path' => '/uploads/a.jpg',
            'sort' => 3,
        ]);

        GalleryItem::create([
            'gallery_block_id' => $galleryContainer->id,
            'image' => '/uploads/a.jpg',
            'caption' => 'Sample',
            'sort' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/admin/cards/' . $card->id . '/editor-data');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $card->id,
                    'title' => 'My Card',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'des',
                    'logo',
                    'image_background',
                    'page_background_image',
                    'header_text_color',
                    'blocks' => [
                        '*' => [
                            'id',
                            'type',
                            'title',
                            'sort',
                            'color',
                            'background_color',
                            'data',
                        ],
                    ],
                ],
            ]);

        $blocks = $response->json('data.blocks');
        $this->assertSame('text', $blocks[0]['type']);
        $this->assertSame('#fef3c7', $blocks[0]['background_color']);
        $this->assertSame('Hello world', $blocks[0]['data']['descriptions']);
        $this->assertSame('vazir', $blocks[0]['data']['typography']['title_font']);
        $this->assertSame('iransans', $blocks[0]['data']['typography']['description_font']);
        $this->assertSame('faq', $blocks[1]['type']);
        $this->assertSame('#ecfeff', $blocks[1]['background_color']);
        $this->assertSame('Working hours?', $blocks[1]['data']['items'][0]['question']);
        $this->assertSame('yekan', $blocks[1]['data']['typography']['faq_question_font']);
        $this->assertSame('tanha', $blocks[1]['data']['typography']['faq_answer_font']);
        $this->assertSame('gallery', $blocks[2]['type']);
        $this->assertSame('#f5f3ff', $blocks[2]['background_color']);
        $this->assertSame('/uploads/a.jpg', $blocks[2]['data']['items'][0]['image']);
        $this->assertSame('vazir', $blocks[2]['data']['typography']['caption_font']);
    }

    public function test_save_editor_persists_blocks_and_nested_items(): void
    {
        $admin = $this->createAdmin(['view-digital-business-cards', 'edit-digital-business-cards']);
        $card = DigitalBusinessCard::create([
            'title' => 'Old Title',
            'des' => 'Old description',
            'logo' => null,
            'image_background' => '/uploads/old-bg.jpg',
            'page_background_image' => '/uploads/old-page-bg.jpg',
            'header_text_color' => '#ffffff',
        ]);

        $payload = [
            'title' => 'My Card',
            'des' => 'Description',
            'logo' => '/uploads/logo.png',
            'image_background' => '/uploads/bg.jpg',
            'page_background_image' => '/uploads/page-bg.jpg',
            'header_text_color' => '#2233aa',
            'blocks' => [
                [
                    'temp_id' => 'tmp_text',
                    'type' => DigitalBusinessCardBlock::TYPE_TEXT,
                    'title' => 'Intro',
                    'sort' => 1,
                    'color' => '#ffffff',
                    'background_color' => '#fef3c7',
                    'data' => [
                        'descriptions' => 'Hello world',
                        'typography' => [
                            'title_font' => 'vazir',
                            'description_font' => 'iransans',
                            'button_font' => 'not-allowed-font',
                        ],
                    ],
                ],
                [
                    'temp_id' => 'tmp_faq',
                    'type' => DigitalBusinessCardBlock::TYPE_FAQ,
                    'title' => 'FAQ',
                    'sort' => 2,
                    'color' => '#f4f4f4',
                    'background_color' => '#ecfeff',
                    'data' => [
                        'typography' => [
                            'faq_question_font' => 'yekan',
                            'faq_answer_font' => 'tanha',
                        ],
                        'items' => [
                            [
                                'temp_id' => 'faq_item_1',
                                'question' => 'Working hours?',
                                'answer' => '9 to 5',
                                'sort' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'temp_id' => 'tmp_gallery',
                    'type' => DigitalBusinessCardBlock::TYPE_GALLERY,
                    'title' => 'Portfolio',
                    'sort' => 3,
                    'color' => '#ffffff',
                    'background_color' => '#f5f3ff',
                    'data' => [
                        'typography' => [
                            'caption_font' => 'vazir',
                        ],
                        'items' => [
                            [
                                'temp_id' => 'gallery_item_1',
                                'image' => '/uploads/a.jpg',
                                'caption' => 'Sample',
                                'sort' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->actingAs($admin, 'admin')->postJson('/admin/cards/' . $card->id . '/save-editor', $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'My Card',
                ],
            ])
            ->assertJsonCount(3, 'data.blocks');

        $this->assertDatabaseHas('digital_business_cards', [
            'id' => $card->id,
            'title' => 'My Card',
            'des' => 'Description',
            'page_background_image' => '/uploads/page-bg.jpg',
            'header_text_color' => '#2233aa',
        ]);

        $this->assertDatabaseHas('digital_business_card_blocks', [
            'digital_business_card_id' => $card->id,
            'type' => DigitalBusinessCardBlock::TYPE_TEXT,
            'title' => 'Intro',
            'sort' => 1,
            'background_color' => '#fef3c7',
        ]);

        $this->assertDatabaseHas('text_blocks', [
            'digital_business_card_id' => $card->id,
            'descriptions' => DigitalBusinessCardTypography::embedTypographyInText('Hello world', [
                'title_font' => 'vazir',
                'description_font' => 'iransans',
            ]),
        ]);

        $this->assertDatabaseHas('faq_blocks', [
            'digital_business_card_id' => $card->id,
            'descriptions' => DigitalBusinessCardTypography::encodeTypography([
                'faq_question_font' => 'yekan',
                'faq_answer_font' => 'tanha',
            ]),
        ]);

        $this->assertDatabaseHas('gallery_blocks', [
            'digital_business_card_id' => $card->id,
            'descriptions' => DigitalBusinessCardTypography::encodeTypography([
                'caption_font' => 'vazir',
            ]),
        ]);

        $this->assertDatabaseHas('faq_items', [
            'question' => 'Working hours?',
            'answer' => '9 to 5',
            'sort' => 1,
        ]);

        $this->assertDatabaseHas('gallery_items', [
            'image' => '/uploads/a.jpg',
            'caption' => 'Sample',
            'sort' => 1,
        ]);

        $this->assertSame('text', $response->json('data.blocks.0.type'));
        $this->assertSame('faq', $response->json('data.blocks.1.type'));
        $this->assertSame('gallery', $response->json('data.blocks.2.type'));
        $this->assertSame('vazir', $response->json('data.blocks.0.data.typography.title_font'));
        $this->assertSame('iransans', $response->json('data.blocks.0.data.typography.description_font'));
        $this->assertNull($response->json('data.blocks.0.data.typography.button_font'));
        $this->assertSame('yekan', $response->json('data.blocks.1.data.typography.faq_question_font'));
        $this->assertSame('tanha', $response->json('data.blocks.1.data.typography.faq_answer_font'));
        $this->assertSame('vazir', $response->json('data.blocks.2.data.typography.caption_font'));
    }

    public function test_editor_routes_require_editor_permissions(): void
    {
        $admin = $this->createAdmin();
        $card = DigitalBusinessCard::create([
            'title' => 'Card',
            'des' => null,
            'logo' => null,
            'image_background' => '/uploads/background.jpg',
            'page_background_image' => '/uploads/page-bg.jpg',
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->actingAs($admin, 'admin')
            ->getJson('/admin/cards/' . $card->id . '/editor-data')
            ->assertForbidden();

        $this->actingAs($admin, 'admin')
            ->postJson('/admin/cards/' . $card->id . '/save-editor', [
                'title' => 'Card',
                'des' => null,
                'logo' => null,
                'image_background' => null,
                'page_background_image' => null,
                'blocks' => [],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_card_and_redirect_to_editor(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin(['create-digital-business-cards']);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->actingAs($admin, 'admin')->post('/admin/cards', [
            'title' => 'New card',
            'des' => 'Created from admin',
            'logo' => UploadedFile::fake()->image('logo.jpg'),
            'image_background' => UploadedFile::fake()->image('background.jpg'),
        ]);

        $card = DigitalBusinessCard::query()->where('title', 'New card')->firstOrFail();

        $response->assertRedirect(route('admin.cards.editor', $card));

        $this->assertDatabaseHas('digital_business_cards', [
            'id' => $card->id,
            'title' => 'New card',
            'des' => 'Created from admin',
        ]);

        $this->assertNotNull($card->logo);
        $this->assertNotNull($card->image_background);
    }

    public function test_admin_can_upload_editor_media_files(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin(['edit-digital-business-cards']);
        $card = DigitalBusinessCard::create([
            'title' => 'Card',
            'des' => null,
            'logo' => null,
            'image_background' => '/uploads/background.jpg',
            'page_background_image' => null,
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->actingAs($admin, 'admin')->post('/admin/cards/' . $card->id . '/upload-media', [
            'field' => 'logo',
            'file' => UploadedFile::fake()->image('logo.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'field' => 'logo',
                ],
            ]);

        $path = $response->json('data.path');

        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_admin_can_upload_page_background_image_for_editor(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin(['edit-digital-business-cards']);
        $card = DigitalBusinessCard::create([
            'title' => 'Card',
            'des' => null,
            'logo' => null,
            'image_background' => '/uploads/background.jpg',
            'page_background_image' => null,
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->actingAs($admin, 'admin')->post('/admin/cards/' . $card->id . '/upload-media', [
            'field' => 'page_background_image',
            'file' => UploadedFile::fake()->image('page-bg.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'field' => 'page_background_image',
                ],
            ]);

        $path = $response->json('data.path');

        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_admin_can_upload_gallery_image_for_editor(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin(['edit-digital-business-cards']);
        $card = DigitalBusinessCard::create([
            'title' => 'Card',
            'des' => null,
            'logo' => null,
            'image_background' => '/uploads/background.jpg',
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->actingAs($admin, 'admin')->post('/admin/cards/' . $card->id . '/upload-media', [
            'field' => 'gallery_image',
            'file' => UploadedFile::fake()->image('gallery.jpg'),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'field' => 'gallery_image',
                ],
            ]);

        $path = $response->json('data.path');

        $this->assertTrue(Storage::disk('public')->exists($path));
    }
}