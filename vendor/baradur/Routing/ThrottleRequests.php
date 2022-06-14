<?php

class ThrottleRequests
{

    public function handle($request, $next)
    {
        if ($this->tokenVerification)
        {
            $next = $this->checkToken($request->route);
            $this->removeOldTokens();
        }
        return $next;
    }

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