<?php

namespace xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks;

use Carbon\CarbonInterface;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Lorisleiva\CronTranslator\CronParsingException;
use Lorisleiva\CronTranslator\CronTranslator;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Repository\Interfaces\SchedulerRepositoryInterface;
use function config;
use function now;

abstract class Task
{
    protected string $uniqueId;

    protected ?Scheduler $monitoredScheduledTask;

    abstract public static function canHandleEvent(Event $event): bool;

    abstract public function defaultName(): ?string;

    abstract public function type(): string;

    public function __construct(protected Event $event, protected SchedulerRepositoryInterface $schedulerRepository)
    {
        $this->uniqueId = (string)Str::uuid();

        if (! empty($this->name())) {
            $this->monitoredScheduledTask = $schedulerRepository->findByName($this->name());
        }
    }

    public function uniqueId(): string
    {
        return $this->uniqueId;
    }

    public function name(): ?string
    {
        return $this->event->monitorName ?? $this->defaultName();
    }

    public function isBeingMonitored(): bool
    {
        return !is_null($this->monitoredScheduledTask);
    }

    public function previousRunAt(): CarbonInterface
    {
        $dateTime = (new CronExpression($this->cronExpression()))->getPreviousRunDate(now());

        return Date::instance($dateTime);
    }

    public function nextRunAt(CarbonInterface $now = null): CarbonInterface
    {
        $dateTime = (new CronExpression($this->cronExpression()))->getNextRunDate(
            $now ?? now(),
            0,
            false,
            $this->timezone()
        );

        $date = Date::instance($dateTime);

        $date->setTimezone(config('app.timezone'));

        return $date;
    }

    public function graceTimeInMinutes()
    {
        return $this->event->graceTimeInMinutes ?? 5;
    }

    public function cronExpression(): string
    {
        return $this->event->getExpression();
    }

    public function timezone(): string
    {
        return (string)$this->event->timezone;
    }

    public function humanReadableCron(): string
    {
        try {
            return CronTranslator::translate($this->cronExpression());
        } catch (CronParsingException $exception) {
            return $this->cronExpression();
        }
    }
}
