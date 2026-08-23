<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDigitalBusinessCardEditorRequest;
use App\Http\Resources\DigitalBusinessCardEditorResource;
use App\Models\DigitalBusinessCard;
use App\Models\DigitalBusinessCardBlock;
use App\Support\DigitalBusinessCardTypography;
use App\Services\DigitalBusinessCardEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DigitalBusinessCardEditorController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private DigitalBusinessCardEditorService $service)
    {
    }

    public function create(): View
    {
        $this->authorize('create', DigitalBusinessCard::class);

        return view('admin.cards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DigitalBusinessCard::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'slug' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('digital_business_cards', 'slug'),
            ],
            'des' => ['nullable', 'string'],
            'logo' => ['required', 'image', 'max:5120'],
            'image_background' => ['required', 'image', 'max:5120'],
        ]);

        $card = DigitalBusinessCard::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'des' => $data['des'] ?? null,
            'logo' => $this->storeUploadedFile($request->file('logo'), 'digital-business-cards/logo'),
            'image_background' => $this->storeUploadedFile($request->file('image_background'), 'digital-business-cards/backgrounds'),
        ]);

        return redirect()->route('admin.cards.editor', $card)->with('success', 'کارت ویزیت جدید ساخته شد.');
    }

    public function uploadMedia(Request $request, DigitalBusinessCard $card): JsonResponse
    {
        $this->authorize('update', $card);

        $maxFileSize = $request->input('field') === 'block_icon' ? 2048 : 5120;

        $validated = $request->validate([
            'field' => ['required', Rule::in(['logo', 'image_background', 'page_background_image', 'block_image', 'block_icon', 'gallery_image'])],
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:' . $maxFileSize],
        ]);

        $path = $this->storeUploadedFile($request->file('file'), $this->uploadDirectoryForField($validated['field']));

        return response()->json([
            'success' => true,
            'message' => 'فایل با موفقیت بارگذاری شد.',
            'data' => [
                'field' => $validated['field'],
                'path' => $path,
                'url' => asset('storage/' . ltrim($path, '/')),
            ],
        ]);
    }

    public function editorData(DigitalBusinessCard $card): JsonResponse
    {
        $card = $this->loadEditorCard($card);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات ویرایشگر با موفقیت دریافت شد.',
            'data' => new DigitalBusinessCardEditorResource($card),
        ]);
    }

    public function saveEditor(SaveDigitalBusinessCardEditorRequest $request, DigitalBusinessCard $card): JsonResponse
    {
        $validatedPayload = $request->validated();
        $savedCard = $this->service->save($card, $validatedPayload);
        $savedCard = $this->loadEditorCard($savedCard);

        $this->persistTypography($savedCard, (array) data_get($validatedPayload, 'blocks', []));
        $savedCard = $this->loadEditorCard($savedCard->fresh());

        return response()->json([
            'success' => true,
            'message' => 'ویرایش کارت با موفقیت ذخیره شد.',
            'data' => new DigitalBusinessCardEditorResource($savedCard),
        ]);
    }

    private function loadEditorCard(DigitalBusinessCard $card): DigitalBusinessCard
    {
        return $card->load($this->editorRelations());
    }

    private function editorRelations(): array
    {
        return [
            'blocks',
            'blocks.textBlock',
            'blocks.linkBlock',
            'blocks.socialBlock',
            'blocks.mapBlock',
            'blocks.faqBlock.items',
            'blocks.galleryBlock.items',
        ];
    }

    private function storeUploadedFile(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store($directory, 'public');
    }

    private function uploadDirectoryForField(string $field): string
    {
        return match ($field) {
            'logo' => 'digital-business-cards/logo',
            'image_background' => 'digital-business-cards/backgrounds',
            'page_background_image' => 'digital-business-cards/backgrounds',
            'block_image' => 'digital-business-cards/blocks',
            'block_icon' => 'digital-business-cards/icons',
            'gallery_image' => 'digital-business-cards/gallery',
            default => 'digital-business-cards/uploads',
        };
    }

    private function persistTypography(DigitalBusinessCard $card, array $blocksPayload): void
    {
        $blocksBySort = $card->blocks->keyBy('sort');

        foreach ($blocksPayload as $index => $blockPayload) {
            $sort = (int) data_get($blockPayload, 'sort', $index + 1);
            /** @var DigitalBusinessCardBlock|null $block */
            $block = $blocksBySort->get($sort);

            if (!$block instanceof DigitalBusinessCardBlock || $block->type !== data_get($blockPayload, 'type')) {
                continue;
            }

            $typography = DigitalBusinessCardTypography::sanitizeTypography(
                data_get($blockPayload, 'data.typography', [])
            );

            $this->persistBlockTypography($block, $typography);
        }
    }

    private function persistBlockTypography(DigitalBusinessCardBlock $block, array $typography): void
    {
        if ($block->type === DigitalBusinessCardBlock::TYPE_TEXT && $block->textBlock) {
            $block->textBlock->descriptions = DigitalBusinessCardTypography::embedTypographyInText(
                $block->textBlock->descriptions,
                $typography,
            );
            $block->textBlock->save();

            return;
        }

        $encodedTypography = DigitalBusinessCardTypography::encodeTypography($typography);

        if ($block->type === DigitalBusinessCardBlock::TYPE_LINK && $block->linkBlock) {
            $block->linkBlock->title = $encodedTypography;
            $block->linkBlock->save();

            return;
        }

        if ($block->type === DigitalBusinessCardBlock::TYPE_SOCIAL && $block->socialBlock) {
            $block->socialBlock->title = $encodedTypography;
            $block->socialBlock->save();

            return;
        }

        if ($block->type === DigitalBusinessCardBlock::TYPE_MAP && $block->mapBlock) {
            $block->mapBlock->title = $encodedTypography;
            $block->mapBlock->save();

            return;
        }

        if ($block->type === DigitalBusinessCardBlock::TYPE_FAQ && $block->faqBlock) {
            $block->faqBlock->descriptions = $encodedTypography;
            $block->faqBlock->save();

            return;
        }

        if ($block->type === DigitalBusinessCardBlock::TYPE_GALLERY && $block->galleryBlock) {
            $block->galleryBlock->descriptions = $encodedTypography;
            $block->galleryBlock->save();
        }
    }
}
