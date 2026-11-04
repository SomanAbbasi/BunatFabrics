<?php

declare(strict_types=1);

namespace App\Admin\Settings;

use App\Admin\CatalogInput;
use App\Auth\Guard;
use App\Core\Session;
use App\Core\View;

final class SettingsController
{
    public function index(): void { $user = Guard::requireAdmin(); $this->render($user, [], ['name'=>$user['name'], 'email'=>$user['email']]); }
    public function profile(): void
    {
        $user = Guard::requireAdmin(); CatalogInput::csrf();
        $data = ['name'=>CatalogInput::text($_POST,'name'), 'email'=>CatalogInput::text($_POST,'email')];
        $errors = (new SettingsService())->profile((int)$user['id'], $data['name'], $data['email']);
        if ($errors !== []) { http_response_code(422); $this->render($user,$errors,$data); return; }
        Session::flash('status','Profile updated.'); redirect('/admin/settings');
    }
    public function password(): void
    {
        $user = Guard::requireAdmin(); CatalogInput::csrf();
        $get = static fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        $errors = (new SettingsService())->password((int)$user['id'], $get('current_password'), $get('password'), $get('password_confirmation'));
        if ($errors !== []) { http_response_code(422); $this->render($user,$errors,['name'=>$user['name'],'email'=>$user['email']]); return; }
        Session::regenerate(); Session::flash('status','Password updated.'); redirect('/admin/settings');
    }
    private function render(array $user, array $errors, array $profile): void
    {
        View::render('admin/settings/index', ['title'=>'Settings','activeNav'=>'settings','user'=>$user,'errors'=>$errors,'profile'=>$profile], 'admin/layout');
    }
}
