<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $all = Student::all();
        return [
            'total'   => $all->count(),
            'paid'    => $all->where('status', 'Fully Paid')->count(),
            'partial' => $all->where('status', 'Partial')->count(),
            'unpaid'  => $all->where('status', 'Unpaid')->count(),
        ];
    }
}