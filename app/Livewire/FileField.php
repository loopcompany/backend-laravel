<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class FileField extends Component
{
    use WithFileUploads;

    public array $images = [];
    public array $previews = [];
    public array $formData = [];


    public function mount($field, $mainData = [])
    {
        if ($mainData['file'] ?? false) {
            foreach ($mainData['file'] ?? [] as $file) {
                if ($file) {
                    $this->previews[] = '/storage/order/' . $file['filename'];
                }
            }
        }
    }


    public function updatedImages()
    {
        if (!isset($this->formData['file'])) {
            $this->formData['file'] = [];
        }
    
        foreach ($this->images as $index => $image) {
            $this->validate([
                "images.$index" => 'image|max:2048',
            ]);
    
            try {
                $path = $image->store('order', 'public');
            } catch (\Exception $e) {
                Log::error('خطا در ذخیره فایل: ' . $e->getMessage());
                continue;
            }
    
            $this->previews[$index] = Storage::url($path);
    
            $this->formData['file'][$index] = [
                'filename' => basename($path),
                'size' => $image->getSize(),
                'type' => $image->getMimeType(),
            ];
        }
    
        $this->formData['file'] = array_values($this->formData['file']);
        $this->dispatch('formDataUpdated', [
            'key' => 'file',
            'data' => $this->formData['file'],
        ]);
    }


    public function removeImage($index)
    {
        if (isset($this->previews[$index])) {
            $file = 'order/' . basename($this->previews[$index]);
            if (Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
            unset($this->previews[$index], $this->images[$index], $this->formData['file'][$index]);
        }

        if (!isset($this->formData['file'])) {
            $this->formData['file'] = [];
        }

        $this->dispatch('formDataUpdated', [
            'key' => 'file',
            'data' => $this->formData['file'],
        ]);
    }


    public function render()
    {
        return view('livewire.file-field');
    }
}
