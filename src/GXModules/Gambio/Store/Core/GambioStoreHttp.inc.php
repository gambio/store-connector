<?php
/* --------------------------------------------------------------
   GambioStoreHttp.php 2020-05-15
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
*/

require_once 'Exceptions/GambioStoreHttpErrorException.inc.php';
require_once 'GambioStoreResponse.inc.php';

/**
 * Class GambioStoreHttp
 */
class GambioStoreHttp
{
    /**
     * Performs an options request
     *
     * @param       $url
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    public function options($url, $options = [])
    {
        $options += [
            CURLOPT_CUSTOMREQUEST => 'OPTIONS',
            CURLOPT_NOBODY        => true,
            CURLOPT_HEADER        => true
        ];
        
        return $this->request($url, $options);
    }
    
    
    /**
     * Performs a curl request
     *
     * @param       $url
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    private function request($url, $options = [])
    {
        $handle = curl_init();
        
        curl_setopt_array($handle, [
                                       CURLOPT_URL            => $url,
                                       CURLOPT_FAILONERROR    => true,
                                       CURLOPT_CONNECTTIMEOUT => 10,
                                       CURLOPT_RETURNTRANSFER => true,
                                   ] + $options);
        
        $headers = [];
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len    = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
            {
                return $len;
            }
            
            $headers[strtoupper(trim($header[0]))][] = trim($header[1]);
            
            return $len;
        });
        
        $body        = curl_exec($handle);
        $information = curl_getinfo($handle);
        $error       = curl_error($handle);
        
        if (!empty($error)) {
            throw new GambioStoreHttpErrorException('The curl request to ' . $url . ' failed with the following error: '
                                                    . $error);
        }
        
        return new GambioStoreResponse($body, $headers, $information);
    }
    
    
    /**
     * Performs a get request
     *
     * @param       $url
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    public function get($url, $options = [])
    {
        return $this->request($url, $options);
    }
    
    
    /**
     * Performs a delete request
     *
     * @param       $url
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    public function delete($url, $options = [])
    {
        $options += [
            CURLOPT_CUSTOMREQUEST => 'DELETE'
        ];
        
        return $this->request($url, $options);
    }
    
    
    /**
     * Performs a put request
     *
     * @param       $url
     * @param array $data
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    public function put($url, $data = [], $options = [])
    {
        $options += [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS    => $data
        ];
        
        return $this->request($url, $options);
    }
    
    
    /**
     * Performs a patch request
     *
     * @param       $url
     * @param array $data
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    public function patch($url, $data = [], $options = [])
    {
        $options += [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS    => $data
        ];
        
        return $this->request($url, $options);
    }
    
    
    /**
     * Performs a post request
     *
     * @param       $url
     * @param array $data
     * @param array $options
     *
     * @return \GambioStoreResponse
     * @throws \GambioStoreHttpErrorException
     */
    public function post($url, $data = [], $options = [])
    {
        $options += [
            CURLOPT_POST       => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $data
        ];
        
        return $this->request($url, $options);
    }
}
