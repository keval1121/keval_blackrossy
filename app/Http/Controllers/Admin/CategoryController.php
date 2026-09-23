<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()->with('parent')->orderBy('display_order')->paginate(30);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', [
            'parents' => Category::query()->parents()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ImageService $images)
    {
        Category::query()->create($this->data($request, $images));

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::query()->parents()->where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category, ImageService $images)
    {
        $category->update($this->data($request, $images, $category));

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->subCategoryProducts()->exists() || $category->children()->exists()) {
            return back()->withErrors(['delete' => 'Move or delete related products/sub-categories first.']);
        }
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    private function data(Request $request, ImageService $images, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'seo_content' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['show_on_homepage'] = $request->boolean('show_on_homepage');
        $data['display_order'] = $data['display_order'] ?? 0;
        if ($request->hasFile('image')) {
            $data['image'] = $images->storeSingle($request->file('image'), 'categories', 800);
        }

        return $data;
    }
}
