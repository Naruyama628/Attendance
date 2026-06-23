<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'work_start' => ['required', 'before:work_end'],
            'work_end' => ['required'],
            
            'break_start.*' => ['after:work_start', 'before:work_end'],
            'break_end.*' => ['after:break_start.*', 'before:work_end'],
            'break_id.*' => ['required'],

            'break_start_create' => ['nullable', 'after:work_start', 'before:work_end'],
            'break_end_create' => ['nullable', 'after:break_start_create', 'before:work_end'],

            'remarks' => ['required'],
            'attendance_id' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'work_start.required' => '出勤時間を入力してください',
            'work_start.before' => '出勤時間もしくは退勤時間が不適切な値です',

            'work_end.required' => '退勤時間を入力してください',

            'break_start.*.after' => '休憩時間が不適切な値です',
            'break_start.*.before' => '休憩時間が不適切な値です',

            'break_end.*.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'break_end.*.after' => '休憩時間が不適切な値です',

            'break_start_create.after' => '休憩時間が不適切な値です',
            'break_start_create.before' => '休憩時間が不適切な値です',

            'break_end_create.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'break_end_create.after' => '休憩時間が不適切な値です',
            
            'remarks.required' => '備考を記入してください',
            ];
    }
}
