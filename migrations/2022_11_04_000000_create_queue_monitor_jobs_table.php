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
                $table->smallIncrements('id');
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
                $table->unsignedSmallInteger('queue_monitor_job_id')->index();
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

                $table->string('exception_id', 36)->nullable();

                $table->unsignedMediumInteger('use_memory_mb')->nullable();
                $table->float('use_cpu', 12, 6)->nullable();

                $table->longText('data')->nullable();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.queues_sizes'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();
                $table->unsignedSmallInteger('queue_id');
                $table->unsignedInteger('size');
                $table->timestamp('created_at');
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.hosts'), function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('name', 64);
                $table->string('alias', 64)->nullable();
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.commands'), function (Blueprint $table) {
                $table->unsignedSmallInteger('id');
                $table->string('command', 64);
                $table->string('class_with_namespace', 64)->nullable();
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.monitor_command'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();
                $table->unsignedSmallInteger('command_id')->index();
                $table->unsignedSmallInteger('host_id')->index();

                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('finished_at')->nullable()->index();
                $table->float('time_elapsed', 12, 6)->nullable()->index();

                $table->boolean('failed')->default(false)->index();

                $table->string('exception_id', 36)->nullable();

                $table->unsignedMediumInteger('use_memory_mb')->nullable();
                $table->float('use_cpu', 12, 6)->nullable();

                $table->timestamp('created_at')->index();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.schedulers'), function (Blueprint $table) {
                $table->unsignedSmallInteger('id');
                $table->string('name');
                $table->string('type')->nullable();
                $table->string('cron_expression');
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.monitor_scheduler'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();
                $table->unsignedSmallInteger('scheduled_id')->index();
                $table->unsignedSmallInteger('host_id')->index();

                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('finished_at')->nullable()->index();
                $table->float('time_elapsed', 12, 6)->nullable()->index();

                $table->boolean('failed')->default(false)->index();

                $table->string('exception_id', 36)->nullable();

                $table->unsignedMediumInteger('use_memory_mb')->nullable();
                $table->float('use_cpu', 12, 6)->nullable();

                $table->timestamp('created_at')->index();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.exceptions'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();
                $table->enum('entity', ['scheduler', 'command', 'job'])->index();
                $table->longText('exception')->nullable();
                $table->text('exception_message')->nullable();
                $table->text('exception_class')->nullable();
                $table->timestamp('created_at')->index();
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
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.command'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.hosts'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_queue'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_command'));
    }
}
