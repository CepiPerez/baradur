<?php

class Schema
{
    static $drop = false;

    static $unique = array();
    static $primary = array();

    public static function checkMainTable()
    {
        $res = DB::select("SHOW TABLES LIKE 'migrations'");

        if (count($res) == 0) {
            Artisan::info("Preparing database.");

            $startTime = microtime(true);

            DB::statement('CREATE TABLE migrations (migration text, applied timestamp)');

            $endTime = microtime(true);
            $time = ($endTime - $startTime) * 1000;
            $time = round($time, 2) . "ms";

            Artisan::lineInfo("Creating migration table", 'DONE', $time);
        }
    }


    private static function addColumn($column)
    {
        if ($column->type == 'DROP') {
            self::$drop = true;
            return '`' . $column->name . '`';
        } else {
            self::$drop = false;
        }

        $col = '`' . $column->name . '` ' . strtoupper($column->type);
        if (isset($column->length)) $col .= ' (' . $column->length . ')';
        else if (isset($column->precision)) $col .= ' (' . $column->precision . ',' . $column->scale . ')';

        if (isset($column->unsigned)) $col .= ' UNSIGNED';
        if (!isset($column->nullable)) $col .= ' NOT NULL';
        if (isset($column->increments)) $col .= ' AUTO_INCREMENT';

        if ($column->type == 'timestamp' && $column->default === null) {
            $col .= ' NULL DEFAULT NULL';
        }

        if (isset($column->default)) $col .= ' DEFAULT ' . $column->default;
        if (isset($column->update)) $col .= ' ON UPDATE ' . $column->update;

        if (isset($column->primary)) self::$primary[] = $column->name;
        if (isset($column->unique)) self::$unique[] = $column->name;

        return $col;
    }


    public static function create()
    {
        self::processTable('CREATE', func_get_args());
    }

    public static function table()
    {
        self::processTable('ALTER', func_get_args());
    }

    public static function dropIfExists($table)
    {
        self::checkMainTable();
        //DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        //DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }


    private static function processTable($action, $values)
    {
        self::checkMainTable();

        $table = array_shift($values);

        if (is_closure($values[0])) {
            list($class, $method) = getCallbackFromString($values[0]);
            $blueprint = new Blueprint();
            executeCallback($class, $method, array(&$blueprint));
        }

        $columns = array();
        $foreigns = array();
        $primary = array();
        $unique = array();

        $foreigns_helper = array();

        foreach ($blueprint as $column) {
            if ($column->type == 'foreign') {
                $foreigns[] = $column;
                $foreigns_helper[] = $column->name;
            }
        }

        foreach ($blueprint as $column) {
            if ($column->type == 'primary') {
                $primary[] = array('value' => $column->value, 'name' => $column->name);
            } elseif ($column->type == 'unique') {
                $unique[] = $column;
            } elseif ($column->type != 'foreign') {
                if (in_array($column->name, $foreigns_helper)) {
                    $column->unsigned = 1;
                }
                $columns[] = self::addColumn($column);
                if ($column->unique)
                    $unique[] = $column;
            }
        }


        $foreigntext = array();

        if (count($foreigns) > 0) {

            foreach ($foreigns as $foreign) {
                $text = '';
                $key = $table . '_' . $foreign->name . '_foreign';

                $text .= 'CONSTRAINT `' . $key . '` FOREIGN KEY (`' . $foreign->name . '`) ' .
                    'REFERENCES `' . $foreign->on . '` (`' . $foreign->references . '`)';

                if (isset($foreign->onDelete))
                    $text .= ' ON DELETE ' . $foreign->onDelete;

                if (isset($foreign->onUpdate))
                    $text .= ' ON UPDATE ' . $foreign->onUpdate;

                $foreigntext[] =  $text;
            }
        }

        $primarytext = array();

        foreach ($primary as $f) {
            $text = '';
            $val = is_array($f['value']) ? implode(', ', $f['value']) : $f['value'];

            if (isset($f['name'])) {
                $text .= 'CONSTRAINT ' . $f['name'] . ' PRIMARY KEY (' . $val . ')';
            } else {
                $text .= 'PRIMARY KEY (' . $val . ')';
            }

            $primarytext[] =  $text;
        }

        foreach (self::$primary as $f) {
            $primarytext[] =  'PRIMARY KEY (' . $f . ')';
        }

        $uniquetext = array();

        foreach ($unique as $f) {
            $key = isset($f->index_name) ? $f->index_name : $table . '_' . $f->name . '_unique';
            $text = 'CONSTRAINT ' . $key . ' UNIQUE (' . $f->name . ')';
            $uniquetext[] =  $text;
        }

        self::$primary = array();
        self::$unique = array();

        $query = null;

        if ($action == 'CREATE') {
            $query = 'CREATE TABLE `' . $table . '` (' . implode(', ', $columns);
            if (count($primarytext) > 0) $query .= ', ' . implode(', ', $primarytext);
            if (count($uniquetext) > 0) $query .= ', ' . implode(', ', $uniquetext);
            if (count($foreigntext) > 0) $query .= ', ' . implode(', ', $foreigntext);
            $query .= ')';
        } elseif ($action == 'ALTER') {
            $query = 'ALTER TABLE `' . $table . '` ';

            if (self::$drop) {
                $query .= 'DROP ' . implode(', DROP ', $columns);
            } else {
                $query .= 'ADD ' . implode(', ADD ', $columns);
            }
        }

        //printf($query . PHP_EOL);
        DB::statement($query);
    }
}
