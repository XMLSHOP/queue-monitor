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
        Schema::connection(config('queue-monitor.connection'))
            ->create(config('queue-monitor.table.monitor_jobs'), function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 64);
                $table->string('name_with_namespace');
                $table->timestamps();
            });

        Schema::connection(config('queue-monitor.connection'))
            ->create(config('queue-monitor.table.monitor_queues'), function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('queue_name', 128);
                $table->string('connection_name', 128)->nullable();
                $table->string('queue_name_started', 128)->nullable();
                $table->string('connection_name_started', 128)->nullable();
                $table->timestamps();
            });

        Schema::connection(config('queue-monitor.connection'))
            ->create(config('queue-monitor.table.monitor'), function (Blueprint $table) {
                $table->increments('id');

                $table->string('job_id')->index();
                $table->unsignedInteger('queue_monitor_job_id')->index();
                $table->unsignedSmallInteger('queue_id')->index();

                $table->timestamp('queued_at')->nullable()->index();
                $table->string('queued_at_exact')->nullable();

                $table->timestamp('started_at')->nullable()->index();
                $table->string('started_at_exact')->nullable();

                $table->float('time_pending_elapsed', 12, 6)->nullable()->index();

                $table->timestamp('finished_at')->nullable()->index();
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

        Schema::connection(config('queue-monitor.connection'))
            ->create(config('queue-monitor.table.monitor_queues_sizes'), function (Blueprint $table) {
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


        Schema::connection(config('queue-monitor.connection'))->dropIfExists(config('queue-monitor.table.monitor_jobs'));
        Schema::connection(config('queue-monitor.connection'))->dropIfExists(config('queue-monitor.table.monitor'));
        Schema::connection(config('queue-monitor.connection'))->dropIfExists(config('queue-monitor.table.monitor_queues'));
        Schema::connection(config('queue-monitor.connection'))->dropIfExists(config('queue-monitor.table.monitor_queues_sizes'));
    }
}
