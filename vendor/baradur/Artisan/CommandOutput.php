<?php

class CommandOutput
{
    protected $current;
    protected $count;

    public function createProgressBar($count)
    {
        $this->current = 1;
        $this->count = $count;
        return $this;

    }

    public function start()
    {
        $this->showStatus();
    }

    public function advance()
    {
        $this->current++;
        $this->showStatus();
    }

    public function finish()
    {
        $this->current = $this->count;
        $this->showStatus();

        flush();
     
        // when done, send a newline
        echo "\n\n";
    }

    function showStatus() {
 
        static $start_time;

        $done = $this->current;
        $total = $this->count;
        $size = 25;

        $len = strlen($total);
        $tdone = str_pad($done, $len, " ", STR_PAD_LEFT);
        
        // if we go over our bound, just ignore it
        if($done > $total) return;
     
        if(empty($start_time)) $start_time=time();
        $now = time();
     
        $perc=(double)($done/$total);
     
        $bar=floor($perc*$size);
     
        $status_bar="\r  $tdone/$total  ";
        $status_bar.=str_repeat("\033[48;5;002m \033[m", $bar);
        if($bar<$size){
            $status_bar.="\033[48;5;002m \033[m";
            $status_bar.=str_repeat("\033[48;5;203m \033[m", $size-$bar);
        } else {
            $status_bar.="\033[48;5;002m \033[m";
        }
     
        $disp=number_format($perc*100, 0);
     
        $status_bar.="  $disp%";
     
        $rate = ($now-$start_time)/$done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);
     
        $elapsed = $now - $start_time;
     
        //$status_bar.= " remaining: ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";
     
        echo "$status_bar  ";
    }




}