<?php
namespace BaseSystem\Controllers;

use Illuminate\Routing\Controller;
use BaseSystem\Foundation\SystemResponseTrait;

/**
 * Class BaseController
 * @package base_system\Controllers
 */
abstract class BaseController extends Controller
{
    use SystemResponseTrait;
}
