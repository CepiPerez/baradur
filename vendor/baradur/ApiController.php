<?php

class ApiController extends BaseController
{

    # Funcion para guardar los tokens registrados
    # Desde aca se van a comprobar para permitir modificaciones
    public function generateToken($user = null)
    {
        global $database;

        if (!$user) $user = "API";

        $token = hash_hmac('sha256', $user, bin2hex(random_bytes(32)));
        $date = new DateTime;
        $timestamp = $date->format('Y-m-d H:i:s');

        DB::statement('CREATE TABLE IF NOT EXISTS api_tokens (`token` VARCHAR(100), `timestamp` TIMESTAMP)');

        DB::statement('INSERT INTO api_tokens (token, timestamp)'. ' VALUES ("' . $token . '", "' .$timestamp . '")');

        return $token;

    }


}
