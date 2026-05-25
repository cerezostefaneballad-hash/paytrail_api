<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TermBalance;
use App\Models\Setting;
use Illuminate\Http\Request;

class SemesterReportController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');

        $query = TermBalance::with('student');

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }
        if ($semester) {
            $query->where('semester', $semester);
        }

        $termBalances = $query->get();
        
        // Get available years for filter
        $availableYears = TermBalance::distinct()->pluck('academic_year')->toArray();

        $report = [];
        foreach ($termBalances as $tb) {
            $report[] = [
                'student_id' => $tb->student->student_id,
                'full_name' => $tb->student->full_name,
                'year_level' => $tb->student->year_level,
                'total_payable' => $tb->total_payable,
                'total_paid' => $tb->total_paid,
                'outstanding_balance' => $tb->outstanding_balance,
                'status' => $tb->status,
                'has_carry_over' => isset($tb->payment_breakdown[0]['type']) && $tb->payment_breakdown[0]['type'] === 'carry_over',
                'carried_amount' => $tb->payment_breakdown[0]['carried_amount'] ?? 0,
            ];
        }

        return response()->json([
            'available_years' => $availableYears,
            'report' => $report,
            'summary' => [
                'total_students' => count($report),
                'total_collected' => collect($report)->sum('total_paid'),
                'total_receivable' => collect($report)->sum('total_payable'),
            ]
        ]);
    }
}