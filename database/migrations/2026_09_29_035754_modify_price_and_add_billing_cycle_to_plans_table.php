<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Note: If this fails due to string->decimal cast with data, you might need to drop and re-add.
            // Assuming no production data yet.
            $table->decimal('price', 8, 2)->change();
            $table->string('billing_cycle')->default('monthly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('price')->change();
            $table->dropColumn('billing_cycle');
        });
    }
};
