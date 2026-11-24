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
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->unsignedInteger('max_seats')->default(10)->after('location_or_link');
            $table->decimal('drop_in_price', 8, 2)->nullable()->after('max_seats');
            $table->string('stripe_payment_link', 500)->nullable()->after('drop_in_price');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_rule');
            $table->foreignId('parent_session_id')->nullable()->after('id')->constrained('service_sessions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->dropForeign(['parent_session_id']);
            $table->dropColumn([
                'parent_session_id',
                'max_seats',
                'drop_in_price',
                'stripe_payment_link',
                'recurrence_end_date',
            ]);
        });
    }
};
