<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\View\View;

class PolicyController extends Controller
{
    public function show(Policy $policy): View
    {
        return view('storefront.policy', ['policy' => $policy]);
    }
}
