<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->string('event_type')->default('live_in_person')->after('title');
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });

        // Backfill existing event_type from services table
        try {
            $services = DB::table('services')->get()->keyBy('id');
            $sessions = DB::table('service_sessions')->get();
            foreach ($sessions as $session) {
                $type = $session->service_id && isset($services[$session->service_id])
                    ? $services[$session->service_id]->type
                    : 'live_in_person';
                DB::table('service_sessions')
                    ->where('id', $session->id)
                    ->update(['event_type' => $type ?: 'live_in_person']);
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->dropColumn('event_type');
        });
    }
};
