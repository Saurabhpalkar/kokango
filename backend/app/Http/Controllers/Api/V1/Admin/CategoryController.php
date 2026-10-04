<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Slug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdminCategoryResource::collection(
            Category::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $category = Category::create([
            'name' => $data['name'],
            'slug' => Slug::unique($data['name'], Category::class),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => (int) Category::max('sort_order') + 1,
        ]);

        return (new AdminCategoryResource($category->loadCount('products')))->response()->setStatusCode(201);
    }

    public function update(CategoryRequest $request, int $id): AdminCategoryResource
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return new AdminCategoryResource($category->loadCount('products'));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            return response()->json(['message' => 'This category still has products. Move or delete them first.'], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
