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
            'first_amount'  => 'nullable|numeric',
            'first_date'    => 'nullable|date',
            'second_amount' => 'nullable|numeric',
            'second_date'   => 'nullable|date',
            'third_amount'  => 'nullable|numeric',
            'third_date'    => 'nullable|date',
            'semester'      => 'nullable',
            'acad_year'     => 'nullable',
        ]);
        $student->update($data);
        return $student->fresh();
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['message' => 'Deleted']);
    }
}