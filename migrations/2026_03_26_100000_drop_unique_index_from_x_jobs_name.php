<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueIndexFromXJobsName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::connection('logs')
            ->table('x_jobs', function (Blueprint $table) {
                $table->dropUnique('x_jobs_name_unique');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::connection('logs')
            ->table('x_jobs', function (Blueprint $table) {
                $table->unique('name');
            });
    }
}
