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

    public function store(Request $request)
    {
        $data = $request->validate([
            'acad_year' => 'required|string',
            'semester' => 'required|string',
            'semestral_fee' => 'required|numeric|min:0',
        ]);

        $oldSettings = Setting::latest()->first();
        $oldYear = $oldSettings->acad_year ?? null;
        $oldSemester = $oldSettings->semester ?? null;

        // Create new settings
        $newSettings = Setting::create($data);

        // Check if term has changed
        $termChanged = ($oldYear !== $data['acad_year']) || ($oldSemester !== $data['semester']);

        if ($termChanged && $oldYear && $oldSemester) {
            $this->carryOverBalances($oldYear, $oldSemester, $data['acad_year'], $data['semester'], $data['semestral_fee']);
        }

        return response()->json($newSettings, 201);
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