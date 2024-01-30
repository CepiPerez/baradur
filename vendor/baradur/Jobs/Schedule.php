<?php

Class Schedule
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    const MINUTE = 0;
    const HOUR = 1;
    const DAY = 2;
    const MONTH = 3;
    const WEEKDAY = 4;
    const YEAR = 5;

    private static $order = array(self::YEAR, self::MONTH, self::DAY, self::WEEKDAY, self::HOUR, self::MINUTE);


    protected static $jobs = array();

    public static function getJobs()
    {
        global $app, $config;

        $list = array();
        
        // Always english
        $locale = config('app.locale');
        $config['app']['locale'] = 'en';

        foreach (self::$jobs as $job) {

            $detail = '';
            $real = $job->callback;

            if (is_closure($job->callback)) {
                $detail = 'Closure > app\console\Kernel.php';
            } elseif (str_contains($job->callback, '::handle')) {
                $detail = 'Closure > ' . $job->callback;
                $arr = explode('::', $job->callback);
                $real = array($arr[0] . '|' . $arr[1] . '|0');
            } elseif (str_contains($job->callback, '::__invoke')) {
                $detail = 'Closure > ' . $job->callback;
                $arr = explode('::', $job->callback);
                $real = array($arr[0] . '|' . $arr[1] . '|0');
            } else {
                $detail = $job->callback;
            }

            $nextDueDate = self::getNextDueDateForEvent($job);
            
            $nextDueDate = now()->diffForHumans($nextDueDate);

            $list[] = array(
                'expression' => $job->expression,
                'command' => $detail,
                'real' => $real,
                'parameters' => $job->parameters,
                'run' => $job->filtersPass(),
                'next' => 'Next due: '. $nextDueDate,
                'now' => $job->isDue($app)
            );
        }

        $config['app']['locale'] = $locale;

        return $list;
    }



    private static function getNextDueDateForEvent($event)
    {
        $res = new CronExpression($event->expression);
        $res = $res->getNextRunDate(Carbon::now());

        return $res;


        $nextDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now())
        );

        if (! $event->isRepeatable()) {
            return $nextDueDate;
        }

        $previousDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getPreviousRunDate(Carbon::now(), 0, true)
        );

        $now = Carbon::now(); //->setTimezone($event->timezone);

        if (! $now->copy()->startOfMinute()->eq($previousDueDate)) {
            return $nextDueDate;
        }

        return $now->endOfSecond();
            //->ceilSeconds($event->repeatSeconds);
    }

    /** @return ScheduleJob */
    public function call($callback, $parameters = array())
    {
        global $_class_list;

        if (is_closure($callback)) {
            $job = new ScheduleJob($callback, $parameters);
        }
        elseif (isset($_class_list[get_class($callback)]) && method_exists($callback, '__invoke')) {
            $job = new ScheduleJob(get_class($callback).'::__invoke', $parameters);
        }

        self::$jobs[] = $job;

        return $job;
    }

    /** @return ScheduleJob */
    public function command($command, $parameters = array())
    {
        global $_class_list;

        if (count($parameters)==0 && count(explode(' ', $command))>1) {
            $arr = explode(' ', $command);
            $command = reset($arr);
            array_shift($arr);
            $parameters = $arr;
        }
        
        if (isset($_class_list[$command]) && is_subclass_of($command, 'Command')) {
            $job = new ScheduleJob($command.'::handle', $parameters);
        } else {
            $job = new ScheduleJob('php artisan ' . $command, $parameters);
        }

        self::$jobs[] = $job;

        return $job;
    }



}