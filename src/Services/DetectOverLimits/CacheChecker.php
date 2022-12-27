<?php

namespace xmlshop\QueueMonitor\Services\DetectOverLimits;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CacheChecker
{
    public const CACHE_KEY = 'monitor-alerts';

    public const DISABLE_CACHE_KEY = 'monitor-alerts-disabled';

    static ?array $_alarmIdentifications = null;

    static ?string $_driver = null;

    public function __construct()
    {
        $this->getCachedAlarms();
    }

    public function getCachedAlarms(): array
    {
        if (null === self::$_alarmIdentifications) {
            try {
                self::$_alarmIdentifications = Cache::store('redis')->get(self::CACHE_KEY . config('app.name', ''), []);
                self::$_driver = 'redis';
            } catch (Exception $e) {
                self::$_alarmIdentifications = Cache::store('file')->get(self::CACHE_KEY . config('app.name', ''), []);
                self::$_driver = null;
            }
        }
        return self::$_alarmIdentifications;
    }

    public function getCachedAlarm(string $alarmId): int|false
    {
        $timestamp = Arr::get($this->getCachedAlarms(), $alarmId, false);
        if ($timestamp && now()->subMinutes(4)->subSeconds(10)->gt(Carbon::createFromTimestamp($timestamp))) {
            unset(self::$_alarmIdentifications[$alarmId]);
            return false;
        }

        if (!$timestamp) {
            return false;
        }

        return self::$_alarmIdentifications[$alarmId];
    }

    public function validate(string $alarmId): bool
    {
        $alarmAdded = $this->getCachedAlarm($alarmId);
        if ($alarmAdded) {
            return false;
        }

        $this->putCachedAlarm($alarmId);

        return true;
    }

    private function putCachedAlarm(string $alarmId): void
    {
        self::$_alarmIdentifications[$alarmId] = time();
    }

    public function complete(): void
    {
        Cache::store(self::$_driver)->put(self::CACHE_KEY . config('app.name', ''), self::$_alarmIdentifications, 4 * 60 + 30); //4m 30s
    }

    public function disableAlarm(int $hours): void
    {
        Cache::store(self::$_driver)->put(self::DISABLE_CACHE_KEY . config('app.name', ''), true, Carbon::now()->addHours($hours));
    }

    public function enableAlarm(): void
    {
        Cache::store(self::$_driver)->forget(self::DISABLE_CACHE_KEY . config('app.name', ''));
    }

    public function isEnabledAlarm(): bool
    {
        $isDisabled = (bool)Cache::store(self::$_driver)->get(self::DISABLE_CACHE_KEY . config('app.name', ''));
        return !$isDisabled;
    }
}