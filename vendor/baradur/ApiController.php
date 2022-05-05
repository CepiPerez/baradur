<?php

require_once('app/lib/random_compat/lib/random.php');

class ApiController
{

    protected $tokenVerification = true;

    # Funcion para guardar los tokens registrados
    # Desde aca se van a comprobar para permitir modificaciones
    public function generateToken($user = null)
    {
        global $database;

        if (!$user) $user = "API";

        $token = hash_hmac('sha256', $user, bin2hex(random_bytes(32)));
        $timestamp = (new DateTime)->format('Y-m-d H:i:s');

        $database->query('CREATE TABLE IF NOT EXISTS api_tokens (`token` VARCHAR(100), 
                        `timestamp` TIMESTAMP)');

        $database->query('INSERT INTO api_tokens (token, timestamp)'
                        . ' VALUES ("' . $token . '", "' .$timestamp . '")');

        return $token;

    }


    public function verify($ruta)
    {
        $result = true;
        if ($this->tokenVerification)
        {
            $result = $this->checkToken($ruta);
            $this->removeOldTokens();
        }
        return $result;

    }


    # Esta funcion verifica el token recibido para evitar ataques.
    # Si el token caduca devuelve sesión expirada
    private function checkToken($ruta)
    {
        global $database;

        $token = getallheaders()['Authorization'];
        $token = str_replace('Bearer ', '', $token);
        
        //echo "TOKEN!!!".$token;

        if ($ruta->method=='POST' || $ruta->method=='PUT' || $ruta->method=='DELETE')
        {

            $query = $database->query('SELECT * FROM api_tokens WHERE token = "' . $token . '" LIMIT 0, 1');

            if (!$query) {
                echo response(array("error"=>"Error en la base de datos."), '401 Unauthorized');
                exit();
            }
    
            $res = $query->fetch_assoc();
            if (!$res) {
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json');
                echo json_encode(array("error"=>"Acceso denegado. Token inexistente"));
                exit();
            }

            $date1 = strtotime(env('API_TOKENS'), strtotime($res['timestamp']));
            $date2 = strtotime(date('Y-m-d H:i:s'));
            if ($date1 < $date2)
            {
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: application/json');
                echo json_encode(array("error"=>"Token expirado"));
                exit();
            }
    
        }
        return true;

    }

    # Esta funcion elimina los tokens que 
    # caducaron segun el tiempo de duracion establecido
    # en API_TOKENS en el archivo .env
    private function removeOldTokens()
    {
        global $database;

        $timestamp = new DateTime();
        $timestamp->modify(str_replace('+', '-', env('API_TOKENS')));
        $database->query('DELETE FROM api_tokens WHERE `timestamp` < "' . 
                        $timestamp->format('Y-m-d H:i:s') . '"');
    }



}
