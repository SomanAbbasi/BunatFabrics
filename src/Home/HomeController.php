<?php

declare(strict_types=1);

namespace App\Home;

use App\Core\View;

/**
 * Public landing page.
 */
final class HomeController
{
    public function index(): void
    {
        View::render('home', [
            'title' => 'Home',
        ]);
    }
}
