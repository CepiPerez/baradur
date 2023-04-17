<?php

class Artisan
{
    public static function command($command, $callback)
    {
        return ConsoleKernel::__addCommand($command, $callback);
    }


    public static function info($message)
    {
        self::showLog(44, 'INFO', $message);
    }

    public static function error($message)
    {
        self::showLog(41, 'ERROR', $message);
    }

    public static function warning($message)
    {
        self::showLog(43, 'WARN', $message);
    }

    public static function exception($exception, $message)
    {
        self::showLog(41, $exception, $message);
    }

    public static function input($message, $secret = false)
    {
        printf("\n\033[32;1m  %s\n\033[m", $message);

        if ($secret) {
            system('stty -echo');
        }

        $result = readline("  > ");

        if ($secret) {
            system('stty echo');
        }

        if (blank($result)) {
            self::error("The answer is required.");
            die();
        }

        printf("\n");

        return $result;
    }

    public static function choice($question, $values, $defaultIndex = null)
    {
        printf("\n\033[32;1m  %s\n\033[m", $question);
        
        foreach ($values as $key => $value) {
            printf("    [");
            printf("\033[33m%s\033[m", $key);
            printf("] $value\n");
        }

        $result = readline("  > ");
        
        if (blank($result) && !is_null($defaultIndex)) {
            $result = $defaultIndex;
        }

        $result = $values[$result];

        if (blank($result)) {
            self::error("The answer is required.");
            die();
        }

        printf("\n");

        return $result;
    }

    private static function showLog($color, $type, $message)
    {
        global $artisan;

        if ($artisan) {
            $message = preg_replace('/(\[.*\])/x', "\033[1m$1\033[m", $message);

            printf("\n  ");
            printf("\033[".$color."m%s", " $type ");
            printf("\033[m %s\n\n", $message);            
        }
    }

    public static function lineInfo($message, $status='done', $time=null)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        $line = "  $message ";

        printf("%s", $line);

        $len = strlen($line) +1;
        $space = $space - $len;
        $line = $time ? ' '.$time.' ' : ' ';
        $spaces = $space - strlen($status) - 12 - strlen($line);

        printf("\033[38;5;240m".str_repeat('.', $spaces)."%s\033[m", $line);
        printf("\033[32;1m%s\033[m", strtoupper($status));
        printf("\n");
    }

    public static function jobInfo($message, $status='done', $time=null)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        $date = now()->toDateTimeString();
        $line = "  $message ";
        printf("\033[38;5;240m  %s\033[m", $date);
        printf("  %s", $line);

        $len = strlen($line) + strlen($time) + 25;
        $space = $space - $len;
        $line = $time ? ' '.$time.' ' : ' ';
        $spaces = $space - strlen($status) - 12 - strlen($line);

        printf("\033[38;5;240m".str_repeat('.', $spaces)."%s\033[m", $line);

        $status = strtoupper($status);

        if ($status=='DONE') {
            printf("\033[32;1m%s\033[m", strtoupper($status));
        } else {
            printf("\033[33;1m%s\033[m", strtoupper($status));
        }

        printf("\n");
    }

    public static function lineTitle($message)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        printf("\033[32;1m  %s \033[m", $message);

        $len = strlen($message) +3;
        $space = $space - $len;
        $spaces = $space - 14;

        printf("\033[38;5;240m".str_repeat('.', $spaces)."\033[m");
        printf("\n");
    }

    public static function lineInfoNormal($message, $status)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        printf("  $message ");

        $len = strlen($message) +3;
        $space = $space - $len;
        $spaces = $space - strlen($status) -15;

        printf("\033[38;5;240m".str_repeat('.', $spaces)."\033[m");
        printf(" $status");
        printf("\n");
    }

    public static function lineInfoWarning($message, $status, $warn=null)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        printf("  $message ");

        $len = strlen($message) +3;
        $space = $space - $len;
        $spaces = $space - strlen($status) -15;

        printf("\033[38;5;240m".str_repeat('.', $spaces)."\033[m");

        if ($warn==1) {
            printf("\033[38;5;172;1m %s\033[m", $status);
        } elseif ($warn==2) {
            printf("\033[32;1m %s\033[m", $status);
        } else {
            printf(" $status");
        }

        printf("\n");
    }

    public static function attributeTitle($message)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        printf("\033[32;1m  %s \033[m", $message);

        $len = strlen($message) +3;
        $space = $space - $len;
        $spaces = $space - 25;

        printf("\033[38;5;240m".str_repeat('.', $spaces)."\033[m");
        printf("type\033[38;5;240m /\033[m");
        printf("\033[38;5;172;1m cast\033[m");

        printf("\n");
    }

    public static function attributeInfo($field, $attributes, $type, $cast)
    {
        $screen_cols = (exec('tput cols'));

        $space = (int)$screen_cols>80? $screen_cols : 90;

        printf("  $field");

        $len = strlen($field) +3;
        $space = $space - $len;
        $spaces = $space - 26;

        if ($attributes!=="") {
            printf("\033[38;5;240m ".$attributes." \033[m");
            $spaces = $spaces - strlen($attributes);
        } else {
            printf(" ");
            $spaces = $spaces +1;
        }

        if ($type) $spaces = $spaces - strlen($type) +10;
        if ($cast) $spaces = $spaces - strlen($cast);

        if ($cast && $type) $spaces -= 3;
        if ($cast && !$type) $spaces += 9;

        printf("\033[38;5;240m".str_repeat('.', $spaces)."\033[m");
        printf(" $type"); 

        if ($type && $cast) {
            printf("\033[38;5;240m /\033[m");
        }

        if ($cast) {
            printf("\033[38;5;172;1m %s\033[m", $cast);
        }

        printf("\n");
    }

    public static function runCommand($command)
    {
        if (!config('app.debug')) {
            abort(403);
        }

        $command = is_array($command)? reset($command) : $command;

        $command = Str::snake($command, ':');

        $proc = Process::path(_DIR_)->start('php artisan '.$command);

        $proc->wait();
        
        if ($proc->successful()) {
            return response()->json(array('result'=>'true', 'command'=>$command), 200);
        } else {
            return response()->json(array('result'=>'false', 'command'=>$command), 200);
        }
    }

    public static function keyGenerate()
    {
        $key = bin2hex(random_bytes(32));
    
        $text = file_get_contents(_DIR_.'.env');
    
        $text = preg_replace('/APP_KEY=(\w*)/x', 'APP_KEY='.$key, $text);
    
        if (file_put_contents(_DIR_.'.env', $text)) {
            Cache::store('file')->setDirectory(_DIR_.'storage/framework/config')->flush();

            return true;
        }

        return false;
    }

    /* public static function migrate()
    {
        global $artisan, $_class_list;

        Schema::checkMainTable();

        $applied = DB::table('migrations')->toBase()->get()->pluck('migration')->toArray();

        $count = 0;

        $files = array();

        $it = new RecursiveDirectoryIterator(_DIR_.'database/migrations');

        foreach(new RecursiveIteratorIterator($it) as $file) {

            if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP') {
                $name = str_ireplace('.php', '', basename($file));
                if (is_file($file)) {
                    $files[] = basename($file);
                }
            }
        }

        sort($files);
        
        foreach($files as $file) {

            $name = str_ireplace('.php', '', basename($file));
            $short =  substr($name, 18);
            //$converted = preg_replace_callback('/(_)(?:[a-z{1}])/', 'upper', $short);
            $converted = Str::camel($short);

            if (!in_array($name, $applied)) {
                            
                if ($artisan && $count==0) {
                    Artisan::info("Running migrations.");
                }

                if ($artisan) {
                    $startTime = microtime(true);
                }
                
                if (!isset($_class_list[$converted])) {
                    CoreLoader::loadClass(_DIR_.'database/migrations/'.$file, false, $converted);
                }

                $class = new $converted;
                $class->up();

                DB::statement('INSERT INTO migrations (migration) VALUES ("'. $name . '")');

                if ($artisan) {
                    $endTime = microtime(true);
                    $time = ($endTime-$startTime)*1000;
                    $time = round($time, 2) ."ms";
                    
                    Artisan::lineInfo($name, 'DONE', $time);
                    //printf("\033[32mMigrated: \033[m ".$name." (". round($time, 2) ."ms)\n");
                }

                ++$count;
            }
        }

        if ($count > 0 && $artisan) printf("\n");

        return $count>0;
    } */

    private static function getStub($class)
    {
        return file_get_contents(_DIR_.'/vendor/baradur/Artisan/Stubs/'.$class.'.stub');
    }


    public static function makeEvent($event, $info=true)
    {
        global $artisan;

        if (!file_exists(_DIR_.'app/events')) {
            mkdir(_DIR_.'app/events');
        }

        $class = self::getStub('event');

        $class = str_replace('{{ class }}', $event, $class);
        
        file_put_contents(_DIR_.'app/events/'.$event.'.php', $class);
        
        if ($artisan && $info) {
            Artisan::info("Event [$event] created successfully.");
        }
    }

    public static function makeListener($listener, $event, $info=true)
    {
        global $artisan;

        if (!file_exists(_DIR_.'app/listeners')) {
            mkdir(_DIR_.'app/listeners');
        }

        $class = self::getStub('listener');

        $class = str_replace('{{ class }}', $listener, $class);
        $class = str_replace('{{ event }}', $event, $class);
        
        file_put_contents(_DIR_.'app/listeners/'.$listener.'.php', $class);
        
        if ($artisan && $info) {
            Artisan::info("Listener [$listener] created successfully.");
        }
    }


}