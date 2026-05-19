<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_id', 'first_name', 'middle_name', 'last_name', 'year_level',
        'first_amount', 'first_date',
        'second_amount', 'second_date',
        'third_amount', 'third_date',
        'semester', 'acad_year', 'issued_by',
    ];

    protected $appends = ['full_name', 'total_paid', 'balance', 'status'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name}, {$this->first_name} " . ($this->middle_name ?? ''));
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) ($this->first_amount + $this->second_amount + $this->third_amount);
    }

    public function getBalanceAttribute(): float
    {
        $fee = Setting::latest()->first()->semestral_fee ?? 0;
        return max(0, $fee - $this->total_paid);
    }

    public function getStatusAttribute(): string
    {
        $fee = Setting::latest()->first()->semestral_fee ?? 0;
        $paid = $this->total_paid;
        if ($paid <= 0) return 'Unpaid';
        if ($paid >= $fee) return 'Fully Paid';
        return 'Partial';
    }
}