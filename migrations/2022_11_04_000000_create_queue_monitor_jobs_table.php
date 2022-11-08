<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueMonitorJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('queue_monitor_jobs'))
            Schema::create('queue_monitor_jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 36);
                $table->string('name_with_namespace');
                $table->timestamps();
            });

        if (!Schema::hasTable('queue_monitor_queues'))
            Schema::create('queue_monitor_queues', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('queue_name', 32)->index();
                $table->timestamps();
            });

        Schema::create('queue_monitor_queues_sizes', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('queue_id');
                $table->unsignedInteger('size');
                $table->timestamp('created_at');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('queue_monitor_jobs');
        Schema::dropIfExists('queue_monitor_queues');
        Schema::dropIfExists('queue_monitor_queues_sizes');
    }
}
