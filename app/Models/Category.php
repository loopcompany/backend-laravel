<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Class Category
 *
 * @property int $id
 * @property string $title
 * @property int|null $parent_id
 * @property string $image_path
 * @property int $has_subcategory
 * @property int $sort
 * @property int $sort2
 * @property string $lang
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Category|null $category
 * @property Collection|Category[] $categories
 *
 * @package App\Models
 */

class Category extends Model implements Sortable
{
	use SoftDeletes;

	use SortableTrait;

	public $sortable = [
		'order_column_name' => 'sort',
		'sort_when_creating' => true,
	];

	protected $table = 'categories';

	protected $casts = [
		'parent_id' => 'int',
		'has_subcategory' => 'int',
		'sort' => 'int',
		'faq_schema' => 'array',
		'sort2' => 'int'
	];

	protected $fillable = [
		'title',
		'slug',
		'parent_id',
		'target_type',
		'image_path',
		'has_subcategory',
		'sort',
		'sort2',
		'start_at',
		'end_at',
		'duration',
		'has_gender',
		'is_fixed',
		'lang',
		 'seo_content',
        'meta_title',
        'meta_description',
        'faq_schema',
	];
	  public function addFaq($question, $answer)
    {
        $faqs = $this->faq_schema ?? [];
        $faqs[] = ['question' => $question, 'answer' => $answer];
        $this->faq_schema = $faqs;
        $this->save();
    }

    public function clearFaq()
    {
        $this->faq_schema = [];
        $this->save();
    }

	public function parent()
	{
		return $this->belongsTo(Category::class, 'parent_id')->withoutGlobalScope(SoftDeletingScope::class);
	}

	
	
	

	public function children()
	{
		return $this->hasMany(Category::class, 'parent_id');
	}

	public function clubs()
	{
		return $this->hasMany(Club::class);
	}

	
	public function orders(): HasMany
	{
		return $this->hasMany(Order::class);
	}

	public function category_fields(): HasMany
	{
		return $this->hasMany(CategoryField::class);
	}
	

	public function brandCategoryField(): HasOne
    {
        return $this->hasOne(CategoryField::class)
            ->whereHas('field', function ($query) {
                $query->withoutGlobalScope(SoftDeletingScope::class)
                    ->where('is_brand', 1);
            });
    }

    public function modelCategoryField(): HasOne
    {
        return $this->hasOne(CategoryField::class)
            ->whereHas('field', function ($query) {
                $query->withoutGlobalScope(SoftDeletingScope::class)
                    ->where('is_model', 1);
            });
    }

	

	public function getAllChildrenIds()
	{
		$ids = DB::select("
			WITH RECURSIVE category_tree AS (
				SELECT id FROM categories WHERE id = ?
				UNION ALL
				SELECT c.id FROM categories c INNER JOIN category_tree ct ON c.parent_id = ct.id
			)
			SELECT id FROM category_tree", [$this->id]);
		return collect($ids)->pluck('id')->toArray();
	}

	public function allDescendants()
	{
		$descendants = collect();

		foreach ($this->children as $child) {
			$descendants->push($child);
			$descendants = $descendants->merge($child->allDescendants());
		}
		return $descendants;
	}

	public function allLeafDescendants()
	{
		$leaves = collect();

		foreach ($this->children as $child) {
			if (!$child->has_subcategory) {
				$leaves->push($child);
			} else {
				$leaves = $leaves->merge($child->allLeafDescendants());
			}
		}

		return $leaves;
	}

	public function leafDescendants(): Collection
	{
		$ids = DB::select("
			WITH RECURSIVE category_tree AS (
				SELECT id, has_subcategory FROM categories WHERE id = ?
				UNION ALL
				SELECT c.id, c.has_subcategory
				FROM categories c
				INNER JOIN category_tree ct ON c.parent_id = ct.id
			)
			SELECT id FROM category_tree WHERE has_subcategory = 0
		", [$this->id]);

		$leafIds = collect($ids)->pluck('id');

		return Category::whereIn('id', $leafIds)->whereHas('category_fields')->orderBy('sort2', 'asc')->get();
	}



	
	

	public function technicians()
	{
		return $this->belongsToMany(Technician::class, 'technician_categories');
	}

	

	
	public function getAllParents()
	{
		$parents = collect([]);
		$parent = $this->parent;

		while (!is_null($parent)) {
			$parents->push($parent);
			$parent = $parent->parent;
		}

		return $parents;
	}

	/**
	 * بررسی اینکه آیا target_type با والد تطابق دارد
	 * قوانین:
	 * - والد both → فرزند می‌تواند user, organization, both باشد
	 * - والد user → فرزند باید user باشد
	 * - والد organization → فرزند باید organization باشد
	 */
	public function canHaveTargetType($targetType): bool
	{
		if (!$this->parent_id || !$this->parent) {
			return true; // دسته‌بندی‌های root می‌توانند هر مقداری داشته باشند
		}

		$parentTargetType = $this->parent->target_type;

		if ($parentTargetType === 'both') {
			return true; // والد both → فرزند می‌تواند هر چیزی باشد
		}

		// والد user یا organization → فرزند باید همان باشد
		return $targetType === $parentTargetType;
	}

	/**
	 * بررسی اینکه آیا می‌توانیم target_type این دسته‌بندی را تغییر دهیم
	 * (با توجه به فرزندان موجود)
	 */
	public function canChangeTargetTypeTo($newTargetType): array
	{
		// اگر فرزند ندارد، آزاد است
		if ($this->children()->count() === 0) {
			return ['can' => true];
		}

		// اگر می‌خواهد به both تغییر کند، هیچ مشکلی نیست
		if ($newTargetType === 'both') {
			return ['can' => true];
		}

		// بررسی فرزندان
		$conflictingChildren = $this->children()
			->where('target_type', '!=', $newTargetType)
			->where('target_type', '!=', 'both')
			->get();

		if ($conflictingChildren->count() > 0) {
			$titles = $conflictingChildren->pluck('title')->join('، ');
			return [
				'can' => false,
				'message' => "این دسته‌بندی دارای فرزندانی با target_type متفاوت است: {$titles}"
			];
		}

		return ['can' => true];
	}

	public function isLeaf()
	{
		return $this->children()->count() == 0;
	}


	public function childArray($model = null)
	{
		$model = $model ?? $this;

		$result = collect();

		// Don't add the current model ($this), only actual children
		if ($model != $this) {
			$result->push($model);
		}

		$children = $model->children;
		if ($children->isNotEmpty()) {
			foreach ($children as $child) {
				$result = $result->merge($this->childArray($child));
			}
		}

		return $result;
	}

	

	

	public function getAllDescendantIds()
	{
		$ids = [$this->id];
		foreach ($this->children as $child) {
			$ids = array_merge($ids, $child->getAllDescendantIds());
		}
		return $ids;
	}

	public function ordersCountWithChildren()
	{
		$allCategoryIds = $this->getAllDescendantIds();

		return \App\Models\Order::whereIn('category_id', $allCategoryIds)->count();
	}


    public function getBreadcrumbTitleAttribute(): string
    {
        $titles = [$this->title];
        $parent = $this->parent;
    
        while ($parent) {
            array_unshift($titles, $parent->title);
            $parent = $parent->parent;
        }
    
        return implode(' > ', $titles);
    }



	public static function boot()
	{
		parent::boot();


		static::addGlobalScope('ordered', function (Builder $query) {
			$query->orderBy('sort', 'asc');
		});
		
		
		static::deleting(function ($category) {
			CategoryField::where('category_id', $category->id)->delete();
			
			// foreach($category->clubs as $club){
			// 	$club->delete();
			// }
			foreach ($category->children as $child) {
					
					CategoryField::where('category_id', $child->id)->delete();

					// foreach($child->clubs as $club){
					// 	$club->delete();
					// }

					$child->delete(); 

			}

		});

	}
}