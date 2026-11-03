<?php

declare(strict_types=1);

namespace App\Admin\Categories;

use App\Admin\CatalogInput;
use App\Auth\Guard;
use App\Core\Session;
use App\Core\View;
use PDOException;

final class CategoryController
{
    public function index(): void
    {
        $user = Guard::requireAdmin();
        $page = CatalogInput::page();
        $listing = (new CategoryService())->listing($page);
        View::render('admin/categories/index', $listing + [
            'title' => 'Categories', 'activeNav' => 'categories', 'user' => $user, 'page' => $page,
        ], 'admin/layout');
    }

    public function create(): void
    {
        $user = Guard::requireAdmin();
        $this->form($user, ['is_active' => '1']);
    }

    public function edit(): void
    {
        $user = Guard::requireAdmin();
        $id = CatalogInput::id($_GET['id'] ?? null);
        $data = $id === null ? null : (new CategoryService())->find($id);
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
        if ($id === null || (new CategoryService())->find($id) === null) {
            CatalogInput::notFound();
        }
        $this->save($user, $id);
    }

    public function state(): void
    {
        Guard::requireAdmin();
        CatalogInput::csrf();
        $id = CatalogInput::id($_POST['id'] ?? null);
        $service = new CategoryService();
        if ($id === null || $service->find($id) === null) {
            CatalogInput::notFound();
        }
        try {
            if ($service->setVisibility($id, CatalogInput::text($_POST, 'is_active'))) {
                Session::flash('status', 'Category updated.');
            } else {
                Session::flash('error', 'Invalid category state.');
            }
        } catch (PDOException $exception) {
            error_log('Category state update failed: ' . $exception->getCode());
            Session::flash('error', 'Unable to save changes. Please try again.');
        }
        redirect('/admin/categories');
    }

    private function save(array $user, ?int $id = null): void
    {
        try {
            $result = (new CategoryService())->save($_POST, $id);
        } catch (PDOException $exception) {
            error_log('Category save failed: ' . $exception->getCode());
            $data = array_map(static fn ($value) => is_string($value) ? $value : '', $_POST);
            http_response_code(503);
            $this->form($user, $data, ['form' => 'Unable to save changes. Please try again.'], $id);
            return;
        }
        if ($result['errors'] !== []) {
            http_response_code(422);
            $this->form($user, $result['data'], $result['errors'], $id);
            return;
        }
        Session::flash('status', 'Category saved.');
        redirect('/admin/categories');
    }

    private function form(array $user, array $data, array $errors = [], ?int $id = null): void
    {
        View::render('admin/categories/form', [
            'title' => $id === null ? 'Add Category' : 'Edit Category',
            'activeNav' => 'categories', 'user' => $user, 'formData' => $data,
            'errors' => $errors, 'id' => $id,
        ], 'admin/layout');
    }
}
