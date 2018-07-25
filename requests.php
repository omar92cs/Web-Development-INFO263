<?php
/**
 * Max length for a uri, lengths over 2000 are rejected by the sever.
 * @var integer
 */
const URIMAXLENGTH = 1900;

/**
 * Calls the given url with the query params and API in the header, responds with ann array of results.
 *
 * @param  string $APIKey      API key for using, Auckland Transport API
 * @param  string $url         Url, API end point.
 * @param  array $queryParams  Associative array of query strings, with field value pairs.
 * @return json                json encoded array of results
 */
function apiCall($APIKey, $url, $queryParams)
{
    $queries = array();
    $start_query = $url;
    if (count($queryParams) === 0)
    {
        $queries[] = $url;
    }
    else
    {
        foreach ($queryParams as $query => $params)
        {
            $queryParamString = "$query=";
            foreach ($params as $index => $value)
            {
                $len = strlen($start_query) + strlen($queryParamString);
                // We have reached the max length of a URI, that will be accepted by the server
                // So we need to batch the call.
                if ($len > URIMAXLENGTH)
                {
                    // Get rid of the last comma
                    $queryParamString = rtrim($queryParamString, ",");
                    $queries[] = $start_query . "?" . $queryParamString;
                    $queryParamString = "$query=$value,";
                }
                $queryParamString .= $value . ",";
            }

            // Add the last uri to batch
            $queryParamString = rtrim($queryParamString, ",");
            $queries[] = $start_query . "?" . $queryParamString;
        }
    }

    $getter = new ParallelGet($queries, $APIKey);
    $getter->execute();
    return $getter->getResults();
}


/**
 * Performs parallel get requests using CURL
 */
class ParallelGet
{
    private $mh;
    private $urls;
    private $ch;

    /**
     * Creates an instance of ParallelGet.
     * @param array $urls   Array of URLs to execute.
     * @param string $APIKey API Key for Auckland Transport API.
     */
    function __construct($urls, $APIKey)
    {
        // Create get requests for each URL
        $this->mh = curl_multi_init();
        $this->urls = $urls;
        $this->ch = array();
        $request_headers = array();
        $request_headers[] = "accept: application/json";
        $request_headers[] = "accept-language: en-US,en;q=0.8";
        $request_headers[] = "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36";
        $request_headers[] = "Ocp-Apim-Subscription-Key: ". $APIKey;

        foreach($urls as $i => $url)
        {
            $this->ch[$i] = curl_init($url);
            curl_setopt($this->ch[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->ch[$i], CURLOPT_HTTPHEADER, $request_headers);
            curl_setopt($this->ch[$i], CURLOPT_CAINFO, getcwd() . '\certs\ca-bundle.crt');
            curl_setopt($this->ch[$i], CURLOPT_ENCODING , "gzip");
            curl_multi_add_handle($this->mh, $this->ch[$i]);

        }
    }
    /**
     * Excutes URLs.
     */
    function execute()
    {
        // Start performing the request
        do {
            $execReturnValue = curl_multi_exec($this->mh, $runningHandles);
        } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);

        // Loop and continue processing the request
        while ($runningHandles && $execReturnValue == CURLM_OK)
        {
            if (curl_multi_select($this->mh) != -1)
            {
                usleep(100);
            }

            do {
                $execReturnValue = curl_multi_exec($this->mh, $runningHandles);
            } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        }

        // Check for any errors
        if ($execReturnValue != CURLM_OK)
        {
            trigger_error("Curl multi read error $execReturnValue\n", E_USER_WARNING);
        }
    }

    /**
     * Gets results from executed URLs.
     * @return array Array of results from URLs.
     */
    function getResults()
    {
        // Extract the content
        foreach ($this->urls as $i => $url)
        {
            // Check for errors
            $curlError = curl_error($this->ch[$i]);

            if ($curlError == "")
            {
                $responseContent = curl_multi_getcontent($this->ch[$i]);
                $res[$i] = $responseContent;
            }
            else
            {
                return "Curl error on handle $i: $curlError\n";
            }
            // Remove and close the handle
            curl_multi_remove_handle($this->mh, $this->ch[$i]);
            curl_close($this->ch[$i]);
        }

        // Clean up the curl_multi handle
        curl_multi_close($this->mh);
        // return the response data
        return $res;
    }
}
 ?>
