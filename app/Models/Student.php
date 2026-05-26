<?php
namespace App\Models;

use App\Models\TermBalance;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    // Add 'carried_over_balance' to $fillable
    protected $fillable = [
        'student_id', 'first_name', 'middle_name', 'last_name', 'year_level',
        'first_amount', 'first_date',
        'second_amount', 'second_date',
        'third_amount', 'third_date',
        'semester', 'acad_year', 'issued_by',
        'carried_over_balance', // ← add this
    ];

    public function getBalanceAttribute(): float
    {
        $fee = Setting::latest()->first()->semestral_fee ?? 0;
        return max(0, $fee + $this->carried_over_balance - $this->total_paid);
    }

    protected $appends = ['full_name', 'total_paid', 'balance', 'status'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name}, {$this->first_name} " . ($this->middle_name ?? ''));
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) ($this->first_amount + $this->second_amount + $this->third_amount);
    }

    public function getStatusAttribute(): string
    {
        $fee         = Setting::latest()->first()->semestral_fee ?? 0;
        $totalPayable = $fee + $this->carried_over_balance;
        $paid        = $this->total_paid;
        if ($paid <= 0) return 'Unpaid';
        if ($paid >= $totalPayable) return 'Fully Paid';
        return 'Partial';
    }

    public function termBalances()
    {
        return $this->hasMany(TermBalance::class);
    }

    // Get balance for a specific term
    public function getTermBalance(string $academicYear, string $semester): ?TermBalance
    {
        return $this->termBalances()
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->first();
    }

    // Get the most recent unpaid balance (for carry-over)
    public function getOutstandingBalanceForCarryOver(): float
    {
        $latestTerm = $this->termBalances()
            ->where('status', '!=', 'Fully Paid')
            ->where('outstanding_balance', '>', 0)
            ->latest('academic_year')
            ->latest('semester')
            ->first();
        
        return $latestTerm ? $latestTerm->outstanding_balance : 0;
    }
}
