<?php
interface Services_Openstreetmap_Transport {

    /**
     * getObject
     *
     * Returns false if the object is not found
     *
     * @param string $type    object type
     * @param mixed  $id      id of object to retrieve
     * @param mixed  $version version of object
     *
     * @return object
     * @throws Services_Openstreetmap_Exception
     */
    public function getObject($type, $id, $version = null);

    /**
     * getObjects
     *
     * @param string $type object type
     * @param array  $ids  ids of objects to retrieve
     *
     * @return void
     */
    public function getObjects($type, array $ids);

    /**
     * Send request to OSM server and return the response.
     *
     * @param string $url       URL
     * @param string $method    GET (default)/POST/PUT
     * @param string $user      user (optional for read-only actions)
     * @param string $password  password (optional for read-only actions)
     * @param string $body      body (optional)
     * @param array  $post_data (optional)
     * @param array  $headers   (optional)
     *
     * @access public
     * @return HTTP_Request2_Response
     * @todo Consider just returning the content?
     * @throws  Services_Openstreetmap_Exception If something unexpected has
     *                                           happened while conversing with
     *                                           the server.
     */
    public function getResponse(
        $url,
        $method = HTTP_Request2::METHOD_GET,
        $user = null,
        $password = null,
        $body = null,
        array $post_data = null,
        array $headers = null
    );

    /**
     * searchObjects
     *
     * @param string $type     object type (e.g. changeset)
     * @param array  $criteria array of criterion objects.
     *
     * @return Services_Openstreetmap_Objects
     */
    public function searchObjects($type, array $criteria);


}