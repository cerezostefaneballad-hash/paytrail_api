<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermBalance extends Model
{
    protected $fillable = [
        'student_id', 'academic_year', 'semester',
        'total_payable', 'total_paid', 'payment_breakdown', 'status'
    ];

    protected $casts = [
        'payment_breakdown' => 'array',
        'total_payable' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function updateStatus(): void
    {
        if ($this->total_paid <= 0) {
            $this->status = 'Unpaid';
        } elseif ($this->total_paid >= $this->total_payable) {
            $this->status = 'Fully Paid';
        } else {
            $this->status = 'Partial';
        }
        $this->saveQuietly();
    }
}