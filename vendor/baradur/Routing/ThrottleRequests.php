<?php

class ThrottleRequests
{

    # Verifies API requests

    public function handle($request, $next)
    {
        $this->removeOldTokens();
        $this->checkToken();

        return $request;
    }

    private function deny($reason)
    {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode(array("error" => $reason));
        exit();
    }

    private function checkToken()
    {
        $token = getallheaders()['Authorization'];
        
        if (!isset($token)) {
            $this->deny("Access denied. Token not found in request");
        }

        $token = str_replace('Bearer ', '', $token);
        
        $res = DB::table('api_tokens')->where('token', $token)->first()->toArray();

        if (!$res) {
            $this->deny("Access denied. Unexistent token");
        }

        $date1 = strtotime(env('API_TOKENS'), strtotime($res['timestamp']));
        $date2 = strtotime(date('Y-m-d H:i:s'));
        if ($date1 < $date2)
        {
            $this->deny("Access denied. Token expired");
        }

        return true;

    }

    # Remove old tokens based on API_TOKENS from .env file
    private function removeOldTokens()
    {
        $timestamp = new DateTime();
        $timestamp->modify(str_replace('+', '-', env('API_TOKENS')));
        DB::statement('DELETE FROM api_tokens WHERE `timestamp` < "' . $timestamp->format('Y-m-d H:i:s') . '"');
    }


}