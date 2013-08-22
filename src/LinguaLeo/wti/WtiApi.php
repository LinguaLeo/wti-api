<?php
namespace LinguaLeo\wti;

class WtiApi {

    private $apiKey;
    private $projectName;

    public function __construct($apiKey, $projectName) {
        $this->apiKey = $apiKey;
        $this->projectName = $projectName;
    }

    public function addUser($email, $locale, $proofread) {
        $params = array(
            "email" => $email,
            "role" => "translator",
            "proofreader" => $proofread,
            "locale" => $locale
        );

        return $this->makeRequest($params, 'users');
    }

    public function addLocale($localeCode) {
        $params = array(
            "id" => $localeCode
        );

        return $this->makeRequest($params, 'locales');
    }

    public function addString($key, $value, $filename) {
        $params = array();

        $params['key'] = $key;
        $params['type'] = "String";
        $params['status'] = "Current";
        $params['file']['file_name'] = $filename;
        $params['translations'] = array(
            array(
                'locale' => 'ru',
                'text' => $value
            )
        );

        return $this->makeRequest($params, 'strings');
    }

    /**
     * @param $params [
     *                  role: can be blank, or one of translator, manager, client. Defaults to blank if left blank.
     *                  filter: can be one of membership, invitation or blank. Defaults to blank if left blank.
     *                  ]
     */
    public function listUsers ($params = array()) {
        return $this->makeRequest($params, 'users', 'GET');
    }

    private function makeRequest ($params, $endpoint, $method = 'POST') {

        $requestURL = "https://webtranslateit.com/api/projects/" . $this->apiKey . "/" . $endpoint;

        $ch = curl_init();

        if ($method == 'GET') {
            $params_array = array();
            foreach ($params as $paramName => $paramValue) {
                $params_array[] = urlencode($paramName) . '=' . urlencode($paramValue);
            }
            $requestURL .= '.json?' . implode('&', $params_array);
        }
        else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        curl_setopt($ch, CURLOPT_URL, $requestURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        return $response;
    }
}