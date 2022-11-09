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
                $table->string('name', 64);
                $table->string('name_with_namespace');
                $table->timestamps();
            });

        if (!Schema::hasTable('queue_monitor'))
            Schema::create('queue_monitor', function (Blueprint $table) {
                $table->increments('id');

                $table->string('job_id')->index();
                $table->unsignedInteger('queue_monitor_job_id')->index();
                $table->string('queue')->nullable();

                $table->timestamp('queued_at')->nullable()->index();
                $table->string('queued_at_exact')->nullable();

                $table->timestamp('started_at')->nullable()->index();
                $table->string('started_at_exact')->nullable();

                $table->timestamp('finished_at')->nullable();
                $table->string('finished_at_exact')->nullable();

                $table->float('time_elapsed', 12, 6)->nullable()->index();

                $table->boolean('failed')->default(false)->index();

                $table->integer('attempt')->default(0);
                $table->integer('progress')->nullable();

                $table->longText('exception')->nullable();
                $table->text('exception_message')->nullable();
                $table->text('exception_class')->nullable();

                $table->longText('data')->nullable();
            });

        if (!Schema::hasTable('queue_monitor_queues'))
            Schema::create('queue_monitor_queues', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('queue_name', 64)->index();
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
        Schema::dropIfExists('queue_monitor');
        Schema::dropIfExists('queue_monitor_jobs');
        Schema::dropIfExists('queue_monitor_queues');
        Schema::dropIfExists('queue_monitor_queues_sizes');
    }
}
