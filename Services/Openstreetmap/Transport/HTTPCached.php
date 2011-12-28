<?php
require_once 'Cache.php';
require_once 'Services/Openstreetmap/Transport/HTTP.php';

class Services_Openstreetmap_Transport_HTTPCached extends Services_Openstreetmap_Transport_HTTP {

    protected $cache;

    public function __construct() {
        parent::__construct();

        $this->setCache(new Cache('file'));
    }

    public function setCache($cache) {
        $this->cache = $cache;
    }

    public function getResponse(
        $url,
        $method = HTTP_Request2::METHOD_GET,
        $user = null,
        $password = null,
        $body = null,
        array $post_data = null,
        array $headers = null
    ) {
        $arguments = array($url, $method, $user, $password, $body, implode(":", (array)$post_data), implode(":", (array)$headers));
        $id = md5(implode(":", $arguments));

        $data = $this->cache->get($id);
        if ($data) {
            $response = new HTTP_Request2_Response();
            $response->setStatus(200);
            $response->setBody($data);

            return $response;
        }

        $response = parent::getResponse(
            $url,
            $method,
            $user,
            $password,
            $body,
            $post_data,
            $headers
        );

        $this->cache->save($id, $response->getBody());

        return $response;
    }
}