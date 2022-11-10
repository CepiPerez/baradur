<?php

class ThrottleRequests
{

    # Verifies API requests

    public function handle($request, $next)
    {
        $next = $this->checkToken($request->route);
        $this->removeOldTokens();

        return $request;
    }

    private function checkToken($ruta)
    {

        $token = getallheaders()['Authorization'];
        $token = str_replace('Bearer ', '', $token);
        
        //echo "TOKEN!!!".$token;

        $res = DB::table('api_tokens')->where('token', $token)->first();

        if (!$res) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Access denied. Unexistent token"));
            exit();
        }

        $date1 = strtotime(env('API_TOKENS'), strtotime($res['timestamp']));
        $date2 = strtotime(date('Y-m-d H:i:s'));
        if ($date1 < $date2)
        {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Token expired"));
            exit();
        }
    

        return true;

    }

    # Remove old tokens based on API_TOKENS from .env file
    private function removeOldTokens()
    {
        global $database;

        $timestamp = new DateTime();
        $timestamp->modify(str_replace('+', '-', env('API_TOKENS')));
        DB::statement('DELETE FROM api_tokens WHERE `timestamp` < "' . $timestamp->format('Y-m-d H:i:s') . '"');
    }


}