<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // Return all courses as JSON for select dropdowns
    public function index(Request $request)
    {
        $courses = Course::All();
        return response()->json($courses);
    }
} 