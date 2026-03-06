<?php

namespace BaseSystem\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class BaseWebController
 */
abstract class BaseWebController extends Controller
{
    protected function render(string $component, array $props = []): Response
    {
        return Inertia::render($component, $props);
    }
}
