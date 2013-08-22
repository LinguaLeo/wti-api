<?php
namespace LinguaLeo\wti;

class WtiApi
{

    private $apiKey;
    public $info;
    private $lastError;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->init();
    }

    private function init()
    {
        $projectInfo = $this->getProjectInfo();
        if (!$projectInfo) {
            throw new \Exception('Request project info failed');
        }
        $this->info = $projectInfo;
    }

    public function getProjectInfo()
    {
        $projectInfo = $this->makeRequest(array(), null, 'GET');

        if ($projectInfo) {
            return $projectInfo['project'];
        } else {
            return false;
        }
    }

    public function getProjectStatistics($params = array())
    {
        return $this->makeRequest($params, 'stats', 'GET');
    }

    public function getTopTranslators($params = array())
    {
        return $this->makeRequest($params, 'top_translators', 'GET');
    }

    public function addUser($email, $locale, $proofread, $role = 'translator')
    {
        $params = array(
            "email" => $email,
            "role" => $role,
            "proofreader" => $proofread,
            "locale" => $locale
        );

        return $this->makeRequest($params, 'users');
    }

    public function addLocale($localeCode)
    {
        $params = array(
            "id" => $localeCode
        );

        return $this->makeRequest($params, 'locales');
    }

    public function addString($key, $value, $filename)
    {
        if (!$filename) {
            throw new \Exception('Filename should be provided');
        }

        $params = array(
            'key' => $key,
            'type' => 'String',
            'status' => 'Current',
            'file' => array(
                'file_name' => $filename
            ),
            'translations' => array(
                array(
                    'locale' => $this->info->source_locale->code,
                    'text' => $value
                )
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
    public function listUsers($params = array())
    {
        return $this->makeRequest($params, 'users', 'GET');
    }

    /**
     * @param $invitation_id
     * @param array $params
     * @return bool|mixed
     *
     * @url https://webtranslateit.com/en/docs/api/user#approve-invitation
     */
    public function approveInvitation($invitation_id, $params = array())
    {
        return $this->makeRequest($params, 'users/' . $invitation_id, 'PUT');
    }


    public function getLastError()
    {
        return $this->lastError;
    }

    private function _getParams($params = array())
    {
        $params_array = array();
        foreach ($params as $paramName => $paramValue) {
            if (!is_null($paramValue)) {
                $params_array[] = urlencode($paramName) . '=' . urlencode($paramValue);
            }
        }
        return implode('&', $params_array);
    }

    private function makeRequest($params = array(), $endpoint = null, $method = 'POST')
    {

        $requestURL = "https://webtranslateit.com/api/projects/" . $this->apiKey;

        if ($endpoint) {
            $requestURL .= "/" . $endpoint;
        }

        $ch = curl_init();

        if ($method == 'GET') {
            $requestURL .= '.json?' . $this->_getParams($params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        curl_setopt($ch, CURLOPT_URL, $requestURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($response['error']) {
            $this->lastError = $response['error'];
            return false;
        } else {
            $this->lastError = null;
            return $response;
        }
    }
}