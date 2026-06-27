<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_report_replies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('ticket_report_id')->index();
            $table->ulid('user_id')->index();
            $table->text('message');
            $table->timestamps();

            $table->foreign('ticket_report_id')
                  ->references('id')
                  ->on('ticket_reports')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_report_replies');
    }
};
