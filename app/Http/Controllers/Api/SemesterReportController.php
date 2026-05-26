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
            $semester     = $request->get('semester');

            $availableYears = TermBalance::distinct()
                ->whereNotNull('academic_year')
                ->pluck('academic_year')
                ->unique()
                ->values()
                ->toArray();

            $query = TermBalance::with('student');

            if ($academicYear) {
                $query->where('academic_year', $academicYear);
            }
            if ($semester) {
                $query->where('semester', $semester);
            }

            $termBalances = $query->get();

            $report = $termBalances
                ->filter(fn($tb) => $tb->student !== null)
                ->map(function ($tb) {
                    $totalPayable = (float) ($tb->total_payable ?? 0);
                    $totalPaid    = (float) ($tb->total_paid   ?? 0);
                    $outstanding  = max(0, $totalPayable - $totalPaid);

                    // payment_breakdown is already cast to array by the model
                    $breakdown   = is_array($tb->payment_breakdown)
                        ? $tb->payment_breakdown
                        : [];

                    // Carry-over entry is the first item in breakdown with type 'carry_over'
                    $carryEntry    = collect($breakdown)->firstWhere('type', 'carry_over');
                    $hasCarryOver  = $carryEntry !== null;
                    $carriedAmount = $hasCarryOver
                        ? (float) ($carryEntry['carried_amount'] ?? 0)
                        : 0.0;

                    return [
                        'student_id'          => $tb->student->student_id ?? '',
                        'full_name'           => $tb->student->full_name  ?? '',
                        'year_level'          => $tb->student->year_level ?? '',
                        'total_payable'       => $totalPayable,
                        'total_paid'          => $totalPaid,
                        'outstanding_balance' => $outstanding,
                        'status'              => $tb->status ?? 'Unpaid',
                        'has_carry_over'      => $hasCarryOver,
                        'carried_amount'      => $carriedAmount,
                    ];
                })
                ->values()
                ->toArray();

            $totalCollected  = array_sum(array_column($report, 'total_paid'));
            $totalReceivable = array_sum(array_column($report, 'total_payable'));

            return response()->json([
                'success'         => true,
                'available_years' => $availableYears,
                'report'          => $report,
                'summary'         => [
                    'total_students'   => count($report),
                    'total_collected'  => $totalCollected,
                    'total_receivable' => $totalReceivable,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching report',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}