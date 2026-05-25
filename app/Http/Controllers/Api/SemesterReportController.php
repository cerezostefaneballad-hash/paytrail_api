<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Setting;
use Illuminate\Http\Request;

class SemesterReportController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');

        if (!$academicYear || !$semester) {
            $settings = Setting::latest()->first();
            $academicYear = $academicYear ?? $settings->acad_year;
            $semester = $semester ?? $settings->semester;
        }

        $students = Student::with(['termBalances' => function($q) use ($academicYear, $semester) {
            $q->where('academic_year', $academicYear)
              ->where('semester', $semester);
        }])->get();

        $report = [];
        foreach ($students as $student) {
            $term = $student->termBalances->first();
            $report[] = [
                'student_id' => $student->student_id,
                'full_name' => $student->full_name,
                'year_level' => $student->year_level,
                'total_payable' => $term->total_payable ?? 0,
                'total_paid' => $term->total_paid ?? 0,
                'outstanding_balance' => $term->outstanding_balance ?? 0,
                'status' => $term->status ?? 'Not Enrolled',
                'has_carry_over' => isset($term->payment_breakdown[0]['type']) && $term->payment_breakdown[0]['type'] === 'carry_over',
                'carried_amount' => $term->payment_breakdown[0]['carried_amount'] ?? 0,
            ];
        }

        return response()->json([
            'academic_year' => $academicYear,
            'semester' => $semester,
            'report' => $report,
            'summary' => [
                'total_students' => count($report),
                'total_collected' => collect($report)->sum('total_paid'),
                'total_receivable' => collect($report)->sum('total_payable'),
            ]
        ]);
    }
}