<?php

class PreventRequestsDuringMaintenance extends MaintenanceMiddleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     */
    protected $except = [
        //'productos*'
    ];
    
}