<?php

Class FieldFactory
{
    private $fields = array();

    public function getField($position)
    {
        if (!isset($this->fields[$position])) {
            switch ($position) {
                case 0:
                    $this->fields[$position] = new FieldFactoryMinute();
                    break;
                case 1:
                    $this->fields[$position] = new FieldFactoryHour();
                    break;
                case 2:
                    $this->fields[$position] = new FieldFactoryDayMonth();
                    break;
                case 3:
                    $this->fields[$position] = new FieldFactoryMonth();
                    break;
                case 4:
                    $this->fields[$position] = new FieldFactoryDayOfWeek();
                    break;
                default:
                    throw new InvalidArgumentException(
                        $position . ' is not a valid position'
                    );
            }
        }

        return $this->fields[$position];
    }



}