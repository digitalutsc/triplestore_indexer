<?php

namespace Drupal\triplestore_indexer;

/**
 * Class IndexingService definition.
 */
class IndexingService implements TripleStoreIndexingInterface {

  /**
   * Implements Serialization.
   */
  public function serialization(array $payload) {
    global $base_url;
    $nid = $payload['nid'];
    $type = str_replace("_", "/", $payload['type']);

    // Make GET request to any content with _format=jsonld.
    $config = \Drupal::config('triplestore_indexer.settings');
    $uri = "$base_url/$type/$nid" . '?_format=jsonld';

    if ($config->get("method_of_auth") == 'digest') {
      $headers = [
          'auth' => [$config->get('admin_username'),base64_decode($config->get('admin_password'))]
      ];
      $request = \Drupal::httpClient()->get($uri, $headers);
    } else {
      $request = \Drupal::httpClient()->get($uri);
    }
    $graph = $request->getBody();
    return $graph;
  }

  /**
   * Load other data associated with a node s.t author, taxonomy terms.
   */
  public function getOtherConmponentAssocNode(array $payload) {
    global $base_url;
    $nid = $payload['nid'];
    $type = str_replace("_", "/", $payload['type']);

    // Make GET request to any content with _format=jsonld.
    $uri = "$base_url/$type/$nid" . '?_format=jsonld';

    // add header if there is authentication is needed
    $config = \Drupal::config('triplestore_indexer.settings');
    if ($config->get("method_of_auth") == 'digest') {
      $headers = [
        'auth' => [$config->get('admin_username'),base64_decode($config->get('admin_password'))]
      ];
      $request = \Drupal::httpClient()->get($uri, $headers);
    } else {
      $request = \Drupal::httpClient()->get($uri);
    }

    // get response body
    $graph = ((array) json_decode($request->getBody()))['@graph'];
    $others = [];
    for ($i = 1; $i < count($graph); $i++) {
      $component = (array) $graph[$i];
      if (strpos($component['@id'], '/taxonomy/term/') !== FALSE) {
        $vocal = get_vocabulary_from_termid(get_termid_from_uri($component['@id']));
        if (isset($vocal)) {
          array_push($others, $component['@id']);
        }
      }
      else {
        array_push($others, $component['@id']);
      }
    }
    return $others;
  }

  /**
   * POST request.
   */
  public function post(string $data) {
    $config = \Drupal::config('triplestore_indexer.settings');
    $server = $config->get("server_url");
    $namespace = $config->get("namespace");

    $curl = curl_init();
    $opts = [
      CURLOPT_URL => "$server/namespace/$namespace/sparql",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_HTTPHEADER => [
        'Content-type: application/ld+json',
      ],
    ];
    curl_setopt_array($curl, $opts);

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
  }

  /**
   * GET request.
   */
  public function get(array $payload) {
    // @todo Implement get() method.
  }

  /**
   * PUT request.
   */
  public function put(array $payload, $data) {
    global $base_url;

    $nid = $payload['nid'];
    $type = str_replace("_", "/", $payload['type']);

    // Delete previously triples indexed.
    $urijld = "<$base_url/$type/$nid" . '?_format=jsonld>';
    $response = $this->delete($urijld);

    // Check ?s may be insert with uri with ?_format=jsonld.
    $result = simplexml_load_string($response);
    if ($result['modified'] <= 0) {
      $uri = "<$base_url/$type/$nid>";
      $response = $this->delete($uri);
    }

    // Index with updated content.
    if (isset($response)) {
      $insert = $this->post($data);
    }
    return $insert;
  }

  /**
   * DELETE request.
   */
  public function delete(string $uri) {
    $curl = curl_init();

    $config = \Drupal::config('triplestore_indexer.settings');
    $server = $config->get("server_url");
    $namespace = $config->get("namespace");
    $username = $config->get("admin_username");
    $password = $config->get("admin_password");

    $opts = [

      CURLOPT_URL => "$server/namespace/$namespace/sparql?s=" . urlencode($uri),
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'DELETE',
      CURLOPT_POSTFIELDS => "",
      CURLOPT_HTTPHEADER => [
        'Content-type: text/plain',
        'Authorization: Basic ' . base64_encode("$username:$password"),
      ],
    ];
    curl_setopt_array($curl, $opts);

    $response = curl_exec($curl);

    // Check for cURL errors
    if ($response === false) {
        $error_message = curl_error($curl);
        curl_close($curl);
        throw new \Exception("cURL error: $error_message");
    }

    // Check HTTP status code for errors
    $http_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status_code < 200 || $http_status_code >= 300) {
        throw new \Exception("HTTP error: $http_status_code");
    }

    return $response;
  }

}
