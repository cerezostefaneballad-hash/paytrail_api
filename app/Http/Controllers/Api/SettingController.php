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

    $prevSetting = Setting::latest()->first();

    if ($prevSetting) {
        $prevFee = (float) $prevSetting->semestral_fee;

        Student::all()->each(function ($student) use ($prevSetting, $prevFee) {
            $totalPayable = $prevFee + (float) $student->carried_over_balance;
            $totalPaid    = (float) $student->first_amount
                          + (float) $student->second_amount
                          + (float) $student->third_amount;
            $outstanding  = max(0, $totalPayable - $totalPaid);

            // Save the closing term balance record before resetting
            $existing = TermBalance::where('student_id', $student->id)
                ->where('academic_year', $prevSetting->acad_year)
                ->where('semester', $prevSetting->semester)
                ->first();

            if ($existing) {
                $existing->update([
                    'total_paid' => $totalPaid,
                    'status'     => $totalPaid <= 0 ? 'Unpaid'
                                  : ($totalPaid >= $totalPayable ? 'Fully Paid' : 'Partial'),
                ]);
            } else {
                TermBalance::create([
                    'student_id'    => $student->id,
                    'academic_year' => $prevSetting->acad_year,
                    'semester'      => $prevSetting->semester,
                    'total_payable' => $totalPayable,
                    'total_paid'    => $totalPaid,
                    'status'        => $totalPaid <= 0 ? 'Unpaid'
                                     : ($totalPaid >= $totalPayable ? 'Fully Paid' : 'Partial'),
                ]);
            }

            // Reset payment slots on student and carry outstanding forward
            $student->update([
                'carried_over_balance' => $outstanding,
                'first_amount'  => 0, 'first_date'  => null,
                'second_amount' => 0, 'second_date' => null,
                'third_amount'  => 0, 'third_date'  => null,
            ]);
        });
    }

    // Now create TermBalance records for the NEW term for every student
    $newFee = (float) $data['semestral_fee'];

    Student::all()->each(function ($student) use ($data, $newFee) {
        $carriedOver = (float) $student->carried_over_balance;

        $breakdown = $carriedOver > 0 ? [[
            'type'             => 'carry_over',
            'from_term'        => ($prevSetting->acad_year ?? '') . ' - ' . ($prevSetting->semester ?? ''),
            'carried_amount'   => $carriedOver,
            'new_semester_fee' => $newFee,
            'date'             => now()->toDateString(),
        ]] : null;

        TermBalance::updateOrCreate(
            [
                'student_id'    => $student->id,
                'academic_year' => $data['acad_year'],
                'semester'      => $data['semester'],
            ],
            [
                'total_payable'     => $newFee + $carriedOver,
                'total_paid'        => 0,
                'payment_breakdown' => $breakdown,
                'status'            => 'Unpaid',
            ]
        );
    });

    return Setting::create($data);
}
}