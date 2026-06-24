<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakCorrectionRequest extends Model
{
    use HasFactory;
        protected $fillable = [
        'attendance_correction_id',
        'break_time_id',
        'requested_break_start',
        'requested_break_end',
    ];

    protected $casts = [
        'requested_break_start' => 'datetime',
        'requested_break_end' => 'datetime',
    ];

    public function attendanceCorrectionRequest()
    {
        return $this->belongsTo(
            AttendanceCorrectionRequest::class,
            'attendance_correction_id'
        );
    }

    public function breakTime()
    {
        return $this->belongsTo(
            BreakTime::class,
            'break_time_id'
        );
    }
}
