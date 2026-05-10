<?php

namespace App\Http\Controllers\Coaches;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AcademicVisibilityController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('coach.documents.index', $request->query());
    }
}
