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
        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.jobs'), function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 64);
                $table->string('name_with_namespace');
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.queues'), function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('queue_name', 128);
                $table->string('connection_name', 128)->nullable();
                $table->string('queue_name_started', 128)->nullable();
                $table->string('connection_name_started', 128)->nullable();
                $table->unsignedSmallInteger('alert_threshold')->nullable();
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.monitor_queue'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();

                $table->string('job_id', 36)->index();
                $table->unsignedInteger('queue_monitor_job_id')->index();
                $table->unsignedSmallInteger('queue_id')->index();
                $table->unsignedSmallInteger('host_id')->index();

                $table->timestamp('queued_at')->nullable()->index();

                $table->timestamp('started_at')->nullable()->index();

                $table->float('time_pending_elapsed', 12, 6)->nullable()->index();

                $table->timestamp('finished_at')->nullable()->index();

                $table->float('time_elapsed', 12, 6)->nullable()->index();

                $table->boolean('failed')->default(false)->index();

                $table->integer('attempt')->default(0);
                $table->integer('progress')->nullable();

                $table->longText('exception')->nullable();
                $table->text('exception_message')->nullable();
                $table->text('exception_class')->nullable();

                $table->longText('data')->nullable();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.queues_sizes'), function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('queue_id');
                $table->unsignedInteger('size');
                $table->timestamp('created_at');
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.hosts'), function (Blueprint $table) {
                $table->unsignedSmallInteger('id');
                $table->string('name', 64);
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.scheduler'), function (Blueprint $table) {
                $table->unsignedSmallInteger('id');
                $table->string('command', 64);
                $table->string('command', 64);
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.monitor_scheduler'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();
                $table->unsignedSmallInteger('scheduler_id')->index();
                $table->unsignedSmallInteger('host_id')->index();

                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('finished_at')->nullable()->index();
                $table->float('time_elapsed', 12, 6)->nullable()->index();

                $table->boolean('failed')->default(false)->index();

                $table->longText('exception')->nullable();
                $table->text('exception_message')->nullable();
                $table->text('exception_class')->nullable();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {


        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.jobs'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.queues'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.queues_sizes'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.scheduler'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.hosts'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_queue'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_scheduler'));
    }
}
