<?php

namespace HexMakina\Deadites;

class Deadites
{
    private string $host;

    public function __construct(string $host = null)
    {
        $this->setHost($host);
    }

    public function setHost(string $host=null): void
    {
        $this->host = $host ?? ($_SERVER['REQUEST_SCHEME'] === 'https' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

        if (!filter_var($this->host, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('INVALID_HOST');
        }
    }

    public function checkURL(string $url, array $checked_urls = [])
    {
        $codes = [];

        $parsed_url = parse_url($url);

        if (!isset($parsed_url['host'])) {
            $url = $this->host . $url;
        }
        else if (!isset($parsed_url['scheme'])) {
            $url = 'https://' . $url;
        }


        // check if URL has already been checked
        if (in_array($url, $checked_urls)) {
            return $codes;
        }

        // add URL to checked URLs
        $checked_urls[] = $url;

        // get headers
        $headers = get_headers($url, true);

        // extract http code from headers
        $http_code = intval(substr($headers[0], 9, 3));

        // follow redirects
        if($http_code >= 300 && $http_code < 400) {
            $codes[] = $http_code;
            $redirect_url = $headers['Location'];
            $redirect_codes = $this->checkURL($redirect_url, $checked_urls);
            if (is_array($redirect_codes)) {
                $codes = array_merge($codes, $redirect_codes);
            } else {
                $codes[] = $redirect_codes;
            }
        } else {
            $codes[] = $http_code;
        }

        return array_reverse($codes);
    }


    // Function to verify image URIs in HTML content
    public function verifyImagesInHTML($html): array
    {
        $errors = [];
        
        // Use regular expression to find image URLs in HTML content
        $pattern = '/<img[^>]+src="([^"]+)"/';

        $errors = [];
        if (preg_match_all($pattern, $html, $matches)) {
            $urls = $matches[1];

            foreach ($urls as $url) {

                if (strpos($url, 'data:image') === 0) {
                    $errors[$url] = "data:image";
                    continue;
                }

                $url = html_entity_decode($url);
                $status = $this->checkURL($url);
                if($status[0] === 200)
                    continue;
                
                $errors[$url]= $status;
            }
        }

        return $errors;
    }

    // write verifyLinksInHTML method to verify links in HTML content
    public function verifyLinksInHTML($html): array
    {
        $errors = [];
        
        // Use regular expression to find image URLs in HTML content
        $pattern = '/<a[^>]+href="([^"]+)"/';

        $errors = [];
        if (preg_match_all($pattern, $html, $matches)) {
            $urls = $matches[1];

            foreach ($urls as $url) {

                $url = html_entity_decode($url);
                $status = $this->checkURL($url);
                if($status[0] === 200)
                    continue;
                
                $errors[$url]= $status;
            }
        }

        return $errors;
    }
}