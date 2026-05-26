<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TermBalance;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show()
    {
        return Setting::latest()->first();
    }
    
    public function store(Request $r)
    {
        $data = $r->validate([
            'acad_year'     => 'required',
            'semester'      => 'required',
            'semestral_fee' => 'required|numeric',
        ]);

        // Before saving the new term, compute each student's outstanding
        // balance from the current term and carry it forward.
        // Payment slots are reset so the new term starts clean.
        $prevSetting = Setting::latest()->first();

        if ($prevSetting) {
            $prevFee = (float) $prevSetting->semestral_fee;

            \App\Models\Student::all()->each(function ($student) use ($prevFee) {
                $totalPayable = $prevFee + (float) $student->carried_over_balance;
                $totalPaid    = (float) $student->first_amount
                            + (float) $student->second_amount
                            + (float) $student->third_amount;
                $outstanding  = max(0, $totalPayable - $totalPaid);

                $student->update([
                    'carried_over_balance' => $outstanding,
                    'first_amount'  => 0, 'first_date'  => null,
                    'second_amount' => 0, 'second_date' => null,
                    'third_amount'  => 0, 'third_date'  => null,
                ]);
            });
        }

        return Setting::create($data);
}

    private function carryOverBalances(string $oldYear, string $oldSemester, string $newYear, string $newSemester, float $newFee): void
    {
        $students = Student::all();

        foreach ($students as $student) {
            $oldTermBalance = $student->getTermBalance($oldYear, $oldSemester);
            
            if ($oldTermBalance && $oldTermBalance->outstanding_balance > 0) {
                // Create new term balance with carried-over balance as starting point
                // The outstanding balance becomes the total_payable for the new term
                TermBalance::create([
                    'student_id' => $student->id,
                    'academic_year' => $newYear,
                    'semester' => $newSemester,
                    'total_payable' => $oldTermBalance->outstanding_balance + $newFee,
                    'total_paid' => 0,
                    'payment_breakdown' => [
                        [
                            'type' => 'carry_over',
                            'from_term' => "$oldYear - $oldSemester",
                            'carried_amount' => $oldTermBalance->outstanding_balance,
                            'new_semester_fee' => $newFee,
                            'date' => now()->toDateString()
                        ]
                    ],
                    'status' => 'Unpaid'
                ]);
            } else {
                // No outstanding balance, just create with new fee
                TermBalance::create([
                    'student_id' => $student->id,
                    'academic_year' => $newYear,
                    'semester' => $newSemester,
                    'total_payable' => $newFee,
                    'total_paid' => 0,
                    'status' => 'Unpaid'
                ]);
            }
        }
    }
}