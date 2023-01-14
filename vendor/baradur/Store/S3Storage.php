<?php

ini_set('display_errors', true);

require_once('SimpleS3.php');

class S3Storage
{
    
    protected $accessKeyId;
    protected $secretKey;
    protected $sessionToken;
    protected $region;
    protected $bucket;
    protected $endpoint;
    protected $url;
    protected $timeoutInSeconds = 5;
    protected $ssl = false;

    protected $instance;


    public function __construct($key, $secret, $bucket, $region, $endpoint, $url)
    {
        $this->accessKeyId = $key;
        $this->secretKey = $secret;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->url = $url;
        
        $ssl = false;

        if (substr($endpoint, 0, 7)=='http://') {
            $endpoint = str_replace('http://', '', $endpoint);
        }
        if (substr($endpoint, 0, 8)=='https://') {
            $this->ssl = true;
            $endpoint = str_replace('https://', '', $endpoint);
        }
        
        $this->endpoint = $endpoint;
        
        // SimpleS3
        //$this->instance = new SimpleS3($this->key, $this->secret, null, $this->region, $this->endpoint);
        
        // S3new
        //$this->instance = new S3($this->key, $this->secret, $ssl, $this->endpoint, $this->region);
        //ddd($this);
        //S3::setSignatureVersion('v2');
        //dump($s3->getBucket('mybucket'));

    }

    private function getPath()
    {
        
        
    }

    private function getUrl()
    {
        return $this->url;        
    }

    public function exists($path)
    {
        $result = $this->s3Request('HEAD', $this->bucket, $path);

        return ($result->getStatusCode()==200)? true : false;
    }

    public function missing($path)
    {
        return !$this->exists($path);
    }

    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        throw new Exception('Function makeDirectory() not supported');
    }

    public function chmod($path, $mode = null)
    {
        throw new Exception('Function chmod() not supported');
    }

    public function put($path, $contents)
    {
        //list($code, $content) = $this->instance->put($this->bucket, $path, $contents);
        $result = $this->s3Request('PUT', $this->bucket, $path, array(), $contents);

        return ($result->getStatusCode()==200)? true : false;
    }

    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path)
        {
            $success = $this->_delete($path);
        }

        return $success;
    }

    private function _delete($path)
    {
        $result = $this->s3Request('DELETE', $this->bucket, $path, array());

        return ($result->getStatusCode()==204)? true : false;
    }


    public function isFile($path)
    {
        $result = $this->s3Request('HEAD', $this->bucket, $path);

        if ($result->getStatusCode()==200)
        {
            $headers = $result->getHeader('Content-Type');
            return $headers[0] != 'application/xml';
        }

        return false;
    }

    public function isDirectory($path)
    {
        $parameters = array();
        $parameters['prefix'] = $path.'/';
        $parameters['max-keys'] = 1;
        
        $result = $this->s3Request('GET', $this->bucket, '', array(), '', true, $parameters);

        if ($result->getStatusCode()==200)
        {
            $xml = simplexml_load_string($result->getBody());
            $array = json_decode(json_encode((array) $xml), true);
            $array = array($xml->getName() => $array);
            //dd($array);
            return isset($array['ListBucketResult']['Contents']);
        }

        return false;
    }

    public function size($path)
    {
        $result = $this->s3Request('HEAD', $this->bucket, $path);

        if ($result->getStatusCode()==200)
        {
            $headers = $result->getHeader('Content-Length');
            return $headers[0];
        }

        return null;        
    }

    public function deleteDirectory($path, $preserve = false)
    {
        $parameters = array();
        $parameters['prefix'] = $path.'/';
        
        $result = $this->s3Request('GET', $this->bucket, '', array(), '', true, $parameters);

        if ($result->getStatusCode()==200)
        {
            $items = array();
            $xml = simplexml_load_string($result->getBody());
            $array = json_decode(json_encode((array) $xml), true);
            $array = array($xml->getName() => $array);
            
            foreach ($array['ListBucketResult']['Contents'] as $content)
            {
                $items[] = $content['Key'];
            }

            $result = true;

            foreach ($items as $item)
            {
                $result = $this->_delete($item);
                if (!$result) break;
            }

            return $result;

        }

        return false;
    }


    public function directories($path)
    {
        throw new Exception('Function directories() not supported');        
    }

    public function get($path)
    {
        $result = $this->s3Request('GET', $this->bucket, $path);

        return ($result->getStatusCode()==200)? $result->getBody() : null;
    }

    public function download($file, $name=null, $headers=null)
    {
        if (!$name) $name = basename($file);

        $parameters = array();
        $parameters['response-content-disposition'] = "attachment";

        $result = $this->s3Request('HEAD', $this->bucket, $file);

        //ddd($this->getAuthenticatedURL($this->bucket, $file));

        if ($result->getStatusCode()==200)
        {
            $location = $this->getAuthenticatedURL($this->bucket, $file);
            $content_type = $result->getHeader('Content-Type]');
            $content_type = $content_type[0];
        
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $content_type);
            header('Content-Disposition: attachment; filename=' . $name);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        
            @readfile($location);
            //send file to browser for download. 
            //echo $result->getBody();
        }

        return null;
        
    }

    public function url($file)
    {
        return $this->getUrl() . $file;        
    }
    
    public function path($file)
    {
        return $this->url($file);
    }

    public function lastModified($path)
    {
        $result = $this->s3Request('HEAD', $this->bucket, $path);

        if ($result->getStatusCode()==200)
        {
            $headers = $result->getHeader('Last-Modified');
            return Carbon::parse($headers[0])->timestamp;
        }

        return null;
    }

    public function copy($source, $dest)
    {
        $headers = array();
        $headers['x-amz-copy-source'] = sprintf('/%s/%s', $this->bucket, rawurlencode($source));
        
        $result = $this->s3Request('PUT', $this->bucket, $dest, $headers);

        return ($result->getStatusCode()==200)? true : false;
    }

    public function move($source, $dest)
    {
        if ($this->copy($source, $dest))
        {
            if ($this->delete($source))
            {
                return true;
            }
            else
            {
                $this->delete($dest);
                return false;
            }
        }
        
        return false;
    }



    /** @return HttpResponse */
    private function s3Request($httpVerb, $bucket, $key, $headers = array(), $body = '', $throwOn404 = true, $parameters=array())
    {
        $uriPath = str_replace('%2F', '/', rawurlencode("{$bucket}/{$key}"));
        $uriPath = '/' . ltrim($uriPath, '/'); //. ($queryString? "?$queryString" : '');
        //$queryString = html_entity_decode($queryString);
        $hostname = $this->endpoint; //$this->getHostname($bucket);
        $headers['host'] = $hostname;

        $parameters = array_map('strval', $parameters); 
		uksort($parameters, array('self', '__sortMetaHeadersCmp'));
		$queryString = http_build_query($parameters, '' /* $numeric_prefix */, '&', PHP_QUERY_RFC3986);

        // Sign the request via headers
        $headers = $this->signRequest($httpVerb, $uriPath, $queryString, $headers, $body);

        /* if ($this->endpoint) {
            $url = $this->endpoint;
        } else {
            $url = "https://$hostname";
        } */
        $url = ($this->ssl? "https://" : "http://") . 
            "{$this->endpoint}/{$bucket}/{$key}" . ($queryString? '?'.$queryString : '');

        //dump($url);

        return Http::withHeaders($headers)
            ->getResults($httpVerb, $url, $body, true);

    }

    private static function __sortMetaHeadersCmp($a, $b)
	{
		$lenA = strlen($a);
		$lenB = strlen($b);
		$minLen = min($lenA, $lenB);
		$ncmp = strncmp($a, $b, $minLen);
		if ($lenA == $lenB) return $ncmp;
		if (0 == $ncmp) return $lenA < $lenB ? -1 : 1;
		return $ncmp;
	}

    private function signRequest($httpVerb, $uriPath, $queryString, $headers, $body)
    {
        $dateAsText = gmdate('Ymd');
        $timeAsText = gmdate('Ymd\THis\Z');
        $scope = "$dateAsText/{$this->region}/s3/aws4_request";
        $bodySignature = hash('sha256', $body);

        $headers['x-amz-date'] = $timeAsText;
        $headers['x-amz-content-sha256'] = $bodySignature;
        if ($this->sessionToken) {
            $headers['x-amz-security-token'] = $this->sessionToken;
        }

        // Ensure the headers always have the same order to have a valid AWS signature
        $headers = $this->sortHeadersByName($headers);

        // https://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-header-based-auth.html
        $headerNamesAsString = implode(';', array_map('strtolower', array_keys($headers)));
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= strtolower($key) . ':' . trim($value) . "\n";
        }

        $canonicalRequest = "$httpVerb\n$uriPath\n$queryString\n$headerString\n$headerNamesAsString\n$bodySignature";

        $stringToSign = "AWS4-HMAC-SHA256\n$timeAsText\n$scope\n" . hash('sha256', $canonicalRequest);
        $signingKey = hash_hmac(
            'sha256',
            'aws4_request',
            hash_hmac(
                'sha256',
                's3',
                hash_hmac(
                    'sha256',
                    $this->region,
                    hash_hmac('sha256', $dateAsText, 'AWS4' . $this->secretKey, true),
                    true
                ),
                true
            ),
            true
        );
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $headers['authorization'] = "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/$scope,SignedHeaders=$headerNamesAsString,Signature=$signature";

        return $headers;
    }

    private function sortHeadersByName($headers)
    {
        ksort($headers, SORT_STRING | SORT_FLAG_CASE);
        return $headers;
    }

    public function getAuthenticatedURL($bucket, $uri, $lifetime=60, $hostBucket = false, $https = false)
	{
		$expires = time() + $lifetime;
		$uri = str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode($uri));
		return sprintf(($https ? 'https' : 'http').'://%s/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',
		// $hostBucket ? $bucket : $bucket.'.s3.amazonaws.com', $uri, self::$__accessKey, $expires,
		$hostBucket ? $bucket : $this->endpoint.'/'.$bucket, $uri, $this->accessKeyId, $expires,
		urlencode($this->getHash("GET\n\n\n{$expires}\n/{$bucket}/{$uri}")));
	}

    private function getHash($string)
	{
		return base64_encode(extension_loaded('hash') ?
		hash_hmac('sha1', $string, $this->secretKey, true) : pack('H*', sha1(
		(str_pad($this->secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
		pack('H*', sha1((str_pad($this->secretKey, 64, chr(0x00)) ^
		(str_repeat(chr(0x36), 64))) . $string)))));
	}

    

}