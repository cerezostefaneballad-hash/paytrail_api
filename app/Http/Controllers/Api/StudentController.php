<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $r)
    {
        $q = Student::query();
        if ($r->year)      $q->where('year_level', $r->year);
        if ($r->semester)  $q->where('semester',   $r->semester);
        if ($r->acad_year) $q->where('acad_year',  $r->acad_year);
        if ($r->search) {
            $s = $r->search;
            $q->where(function ($x) use ($s) {
                $x->where('student_id', 'like', "%$s%")
                  ->orWhere('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%");
            });
        }
        return $q->orderBy('last_name')->get();
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'student_id'  => 'required|unique:students',
            'first_name'  => 'required',
            'middle_name' => 'nullable',
            'last_name'   => 'required',
            'year_level'  => 'required',
        ]);
        $data['issued_by'] = $r->user()->username;
        return Student::create($data);
    }

    public function show(Student $student)
    {
        return $student;
    }

public function update(Request $r, Student $student)
{
    $data = $r->validate([
        'student_id'    => 'required|unique:students,student_id,' . $student->id,
        'first_name'    => 'required',
        'middle_name'   => 'nullable',
        'last_name'     => 'required',
        'year_level'    => 'required',
        'first_amount'  => 'nullable|numeric|min:0',
        'first_date'    => 'nullable|date',
        'second_amount' => 'nullable|numeric|min:0',
        'second_date'   => 'nullable|date',
        'third_amount'  => 'nullable|numeric|min:0',
        'third_date'    => 'nullable|date',
        'semester'      => 'nullable',
        'acad_year'     => 'nullable',
    ]);

    // Prevent total payments from exceeding total payable
    $fee          = (float) (\App\Models\Setting::latest()->first()->semestral_fee ?? 0);
    $carriedOver  = (float) $student->carried_over_balance;
    $totalPayable = $fee + $carriedOver;

    $total = (float) ($data['first_amount']  ?? 0)
           + (float) ($data['second_amount'] ?? 0)
           + (float) ($data['third_amount']  ?? 0);

    if ($total > $totalPayable) {
        return response()->json([
            'message' => 'Total payments cannot exceed the total payable amount of ₱' . number_format($totalPayable, 2),
            'errors'  => [
                'total_amount' => ['Total payments (₱' . number_format($total, 2) . ') exceed total payable (₱' . number_format($totalPayable, 2) . ').']
            ]
        ], 422);
    }

    $student->update($data);

    // ── Sync the TermBalance record for the current term ──────────────
    $setting = \App\Models\Setting::latest()->first();

    if ($setting) {
        $termBalance = \App\Models\TermBalance::where('student_id', $student->id)
            ->where('academic_year', $setting->acad_year)
            ->where('semester',      $setting->semester)
            ->first();

        $newTotalPaid = (float) ($data['first_amount']  ?? 0)
                      + (float) ($data['second_amount'] ?? 0)
                      + (float) ($data['third_amount']  ?? 0);

        $status = match(true) {
            $newTotalPaid <= 0              => 'Unpaid',
            $newTotalPaid >= $totalPayable  => 'Fully Paid',
            default                         => 'Partial',
        };

        if ($termBalance) {
            // Update existing record
            $termBalance->update([
                'total_paid'   => $newTotalPaid,
                'status'       => $status,
            ]);
        } else {
            // No record exists yet for this term — create it
            $carriedOver  = (float) $student->carried_over_balance;
            $breakdown    = $carriedOver > 0 ? [[
                'type'             => 'carry_over',
                'carried_amount'   => $carriedOver,
                'new_semester_fee' => $fee,
                'date'             => now()->toDateString(),
            ]] : null;

            \App\Models\TermBalance::create([
                'student_id'        => $student->id,
                'academic_year'     => $setting->acad_year,
                'semester'          => $setting->semester,
                'total_payable'     => $totalPayable,
                'total_paid'        => $newTotalPaid,
                'payment_breakdown' => $breakdown,
                'status'            => $status,
            ]);
        }
    }

    return $student->fresh();
}

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['message' => 'Deleted']);
    }
}