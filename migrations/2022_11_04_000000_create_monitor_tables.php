<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.jobs'), function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('name', 64)->unique();
                $table->string('name_with_namespace')->unique();
                $table->unsignedSmallInteger('failures_amount_threshold')->nullable();
                $table->unsignedSmallInteger('pending_amount_threshold')->nullable();
                $table->unsignedSmallInteger('pending_time_threshold')->nullable();
                $table->float('pending_time_to_previous_factor', 12, 6)->nullable();
                $table->float('execution_time_to_previous_factor', 12, 6)->nullable();
                $table->boolean('ignore')->default(false);
                $table->boolean('ignore_all_besides_failures')->default(false);
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.queues'), function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('queue_name', 128);
                $table->string('connection_name', 128);
                $table->unsignedSmallInteger('alert_threshold')->nullable();
                $table->timestamps();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.monitor_queue'), function (Blueprint $table) {
                $table->uuid('uuid')->primary();
                $table->string('job_id', 36)->index();
                $table->unsignedSmallInteger('queue_monitor_job_id')->index();
                $table->unsignedSmallInteger('queue_id')->index();
                $table->unsignedSmallInteger('factual_queue_id')->nullable();
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
                $table->smallIncrements('id');
                $table->string('command', 64)->unique();
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
                $table->unsignedMediumInteger('pid')->nullable();
                $table->unsignedMediumInteger('use_memory_mb')->nullable();
                $table->float('use_cpu', 12, 6)->nullable();
                $table->timestamp('created_at')->index();
            });

        Schema::connection(config('monitor.db.connection'))
            ->create(config('monitor.db.table.schedulers'), function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->string('name')->unique();
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
                $table->unsignedSmallInteger('ppid');
                $table->timestamp('finished_at')->nullable()->index();
                $table->float('time_elapsed', 12, 6)->nullable()->index();
                $table->boolean('failed')->default(false)->index();
                $table->string('exception_id', 36)->nullable();
                $table->unsignedMediumInteger('pid')->nullable();
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
     */
    public function down(): void
    {
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.jobs'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.queues'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.queues_sizes'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.commands'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.hosts'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_queue'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_command'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.schedulers'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.monitor_scheduler'));
        Schema::connection(config('monitor.db.connection'))->dropIfExists(config('monitor.db.table.exceptions'));
    }
}
