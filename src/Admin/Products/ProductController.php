<?php

declare(strict_types=1);

namespace App\Admin\Products;

use App\Admin\CatalogInput;
use App\Auth\Guard;
use App\Core\Session;
use App\Core\View;
use PDOException;

final class ProductController
{
    public function index(): void
    {
        $user = Guard::requireAdmin();
        $page = CatalogInput::page();
        $listing = (new ProductService())->listing($page);
        View::render('admin/products/index', $listing + [
            'title' => 'Products', 'activeNav' => 'products', 'user' => $user, 'page' => $page,
        ], 'admin/layout');
    }

    public function create(): void
    {
        $user = Guard::requireAdmin();
        $this->form($user, ['status' => 'draft', 'unit' => 'meter']);
    }

    public function edit(): void
    {
        $user = Guard::requireAdmin();
        $id = CatalogInput::id($_GET['id'] ?? null);
        $data = $id === null ? null : (new ProductService())->find($id);
        if ($data === null) {
            CatalogInput::notFound();
        }
        $this->form($user, $data, [], $id);
    }

    public function store(): void
    {
        $user = Guard::requireAdmin();
        CatalogInput::csrf();
        $this->save($user);
    }

    public function update(): void
    {
        $user = Guard::requireAdmin();
        CatalogInput::csrf();
        $id = CatalogInput::id($_POST['id'] ?? null);
        if ($id === null || (new ProductService())->find($id) === null) {
            CatalogInput::notFound();
        }
        $this->save($user, $id);
    }

    public function deleteImage(): void
    {
        Guard::requireAdmin();
        CatalogInput::csrf();
        $imageId = CatalogInput::id($_POST['image_id'] ?? null);
        $productId = CatalogInput::id($_POST['product_id'] ?? null);
        if ($imageId === null || $productId === null || (new ProductService())->find($productId) === null) {
            CatalogInput::notFound();
        }
        if ((new ProductImageService())->delete($imageId, $productId)) {
            Session::flash('status', 'Image removed.');
        } else {
            Session::flash('error', 'Image could not be removed.');
        }
        redirect('/admin/products/edit?id=' . $productId);
    }

    public function state(): void
    {
        Guard::requireAdmin();
        CatalogInput::csrf();
        $id = CatalogInput::id($_POST['id'] ?? null);
        $service = new ProductService();
        if ($id === null || $service->find($id) === null) {
            CatalogInput::notFound();
        }
        try {
            if ($service->setStatus($id, CatalogInput::text($_POST, 'status'))) {
                Session::flash('status', 'Product updated.');
            } else {
                Session::flash('error', 'Invalid product state.');
            }
        } catch (PDOException $exception) {
            error_log('Product state update failed: ' . $exception->getCode());
            Session::flash('error', 'Unable to save changes. Please try again.');
        }
        redirect('/admin/products');
    }

    private function save(array $user, ?int $id = null): void
    {
        $service = new ProductService();
        $imageService = new ProductImageService();
        $existing = $id === null ? null : $service->find($id);
        $imageErrors = $imageService->validate($_FILES);

        try {
            $result = $service->save($_POST, $id);
        } catch (PDOException $exception) {
            error_log('Product save failed: ' . $exception->getCode());
            $data = array_map(static fn ($value) => is_string($value) ? $value : '', $_POST);
            http_response_code(503);
            $this->form($user, $data, ['form' => 'Unable to save changes. Please try again.'], $id);
            return;
        }

        $errors = $result['errors'];
        if ($imageErrors !== []) {
            $errors['images'] = implode(' ', $imageErrors);
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->form($user, $result['data'], $errors, $id);
            return;
        }

        $productId = (int) $result['id'];
        $categoryId = (int) $result['data']['category_id'];

        if ($existing !== null) {
            $imageService->moveForCategory($productId, (int) $existing['category_id'], $categoryId);
        }

        $uploadErrors = $imageService->store($productId, $categoryId, $_FILES);
        if ($uploadErrors !== []) {
            Session::flash('error', implode(' ', $uploadErrors));
        }

        Session::flash('status', 'Product saved.');
        redirect('/admin/products');
    }

    private function form(array $user, array $data, array $errors = [], ?int $id = null): void
    {
        View::render('admin/products/form', [
            'title' => $id === null ? 'Add Product' : 'Edit Product',
            'activeNav' => 'products', 'user' => $user, 'formData' => $data,
            'errors' => $errors, 'id' => $id,
            'categories' => (new \App\Admin\Categories\CategoryService())->options(),
            'images' => $id === null ? [] : (new ProductImageService())->forProduct($id),
        ], 'admin/layout');
    }
}
