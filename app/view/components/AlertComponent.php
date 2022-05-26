<?php

class AlertComponent extends Component
{
    public $type;


    public function __construct($type)
    {
        //echo "Construc: "; dd($type);
        $this->type = $type;
    }

    public function render()
    {
        return view('components.alert');
    }

    public function prueba()
    {
        if ($this->type == 'danger')
            return 'test';
    }
}
