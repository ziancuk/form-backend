<?php

namespace App\Http\Controllers;

use App\Http\Repositories\EmployeeRepository;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function action($action, Request $request)
    {
        $repo = new EmployeeRepository($request);
        return $repo->$action();
    }
}
