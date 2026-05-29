<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * linkedin_url, summary, portfolio_link
     */
    public function up(): void
    {
        Schema::table('job_seeker_profiles', function (Blueprint $table) {
            $table->string('linkedin_url')->nullable()->after('disability_type');
            $table->string('portfolio_url')->nullable()->after('linkedin_url');
            $table->text('summary')->nullable()->after('portfolio_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_seeker_profiles', function (Blueprint $table) {
            $table->dropColumn(['linkedin_url', 'portfolio_url', 'summary']);
        });
    }
};
