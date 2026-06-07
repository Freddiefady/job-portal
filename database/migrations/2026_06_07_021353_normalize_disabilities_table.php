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
        // 1. Create disabilities table if not exists
        if (! Schema::hasTable('disabilities')) {
            Schema::create('disabilities', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // 2. Create disability_job_posting pivot table if not exists
        if (! Schema::hasTable('disability_job_posting')) {
            Schema::create('disability_job_posting', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
                $table->foreignId('disability_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 3. Migrate data from the old approved_disabilities table if it exists
        if (Schema::hasTable('approved_disabilities')) {
            $oldRecords = DB::table('approved_disabilities')->get();

            foreach ($oldRecords as $record) {
                $jobPostingId = null;
                $disabilityName = null;

                if (property_exists($record, 'job_postings_id')) {
                    $jobPostingId = $record->job_postings_id;
                } elseif (property_exists($record, 'job_postings')) {
                    $jobPostingId = $record->job_postings;
                }

                if (property_exists($record, 'approved_disability')) {
                    $disabilityName = $record->approved_disability;
                }

                if ($jobPostingId && $disabilityName) {
                    $trimmedDisability = mb_substr(trim($disabilityName), 0, 255);
                    if ($trimmedDisability !== '') {
                        $disabilityId = DB::table('disabilities')
                            ->whereRaw('LOWER(name) = ?', [mb_strtolower($trimmedDisability)])
                            ->value('id');

                        if (! $disabilityId) {
                            $disabilityId = DB::table('disabilities')->insertGetId([
                                'name' => $trimmedDisability,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        $exists = DB::table('disability_job_posting')
                            ->where('job_posting_id', $jobPostingId)
                            ->where('disability_id', $disabilityId)
                            ->exists();

                        if (! $exists) {
                            DB::table('disability_job_posting')->insert([
                                'job_posting_id' => $jobPostingId,
                                'disability_id' => $disabilityId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            Schema::dropIfExists('approved_disabilities');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('approved_disabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_postings')->constrained()->cascadeOnDelete();
            $table->string('approved_disability');
            $table->timestamps();
        });

        if (Schema::hasTable('disability_job_posting')) {
            $records = DB::table('disability_job_posting')
                ->join('disabilities', 'disabilities.id', '=', 'disability_job_posting.disability_id')
                ->select('disability_job_posting.job_posting_id', 'disabilities.name')
                ->get();

            foreach ($records as $record) {
                DB::table('approved_disabilities')->insert([
                    'job_postings' => $record->job_posting_id,
                    'approved_disability' => $record->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::dropIfExists('disability_job_posting');
        Schema::dropIfExists('disabilities');
    }
};
