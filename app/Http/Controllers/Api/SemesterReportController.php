<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermBalance;
use Illuminate\Http\Request;

class SemesterReportController extends Controller
{
    public function index(Request $request)
    {
        try {
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
            $availableYears = TermBalance::distinct()
                ->pluck('academic_year')
                ->filter()
                ->values()
                ->toArray();

            $report = [];
            foreach ($termBalances as $tb) {
                // Skip if student doesn't exist
                if (!$tb->student) {
                    continue;
                }

                // Safely handle payment_breakdown
                $paymentBreakdown = is_array($tb->payment_breakdown) 
                    ? $tb->payment_breakdown 
                    : json_decode($tb->payment_breakdown, true) ?? [];

                $hasCarryOver = isset($paymentBreakdown[0]['type']) 
                    && $paymentBreakdown[0]['type'] === 'carry_over';

                $report[] = [
                    'student_id' => $tb->student->student_id ?? '',
                    'full_name' => $tb->student->full_name ?? '',
                    'year_level' => $tb->student->year_level ?? '',
                    // Cast to float explicitly
                    'total_payable' => (float) ($tb->total_payable ?? 0),
                    'total_paid' => (float) ($tb->total_paid ?? 0),
                    'outstanding_balance' => (float) ($tb->outstanding_balance ?? 0),
                    'status' => $tb->status ?? 'Not Enrolled',
                    'has_carry_over' => $hasCarryOver,
                    'carried_amount' => (float) ($paymentBreakdown[0]['carried_amount'] ?? 0),
                ];
            }

            // Calculate summary with explicit float casting
            $totalCollected = 0.0;
            $totalReceivable = 0.0;
            
            foreach ($report as $item) {
                $totalCollected += $item['total_paid'];
                $totalReceivable += $item['total_payable'];
            }

            return response()->json([
                'success' => true,
                'available_years' => $availableYears,
                'report' => $report,
                'summary' => [
                    'total_students' => count($report),
                    'total_collected' => $totalCollected,
                    'total_receivable' => $totalReceivable,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}