<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_correction_id')
                ->constrained('attendance_correction_requests')
                ->onDelete('cascade');

            $table->foreignId('break_time_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->time('requested_break_start')->nullable();
            $table->time('requested_break_end')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_correction_requests');
    }
}
