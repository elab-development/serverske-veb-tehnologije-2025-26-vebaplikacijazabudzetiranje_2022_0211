<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE expenses
             ADD CONSTRAINT chk_expenses_amount_positive
             CHECK (amount > 0)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'ALTER TABLE expenses
             DROP CHECK chk_expenses_amount_positive'
        );
    }
};
