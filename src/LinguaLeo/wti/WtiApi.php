<?php

namespace LinguaLeo\wti;

class WtiApi
{

    public $info;
    /** @var string */
    private $apiKey;
    /** @var resource  */
    private $resource = null;
    /** @var WtiApiRequest */
    private $request;

    public function __construct($apiKey, $initProjectInfo = true)
    {
        $this->apiKey = $apiKey;
        $this->resource = curl_init();
        if ($initProjectInfo) {
            $this->init();
        }
    }

    public function __destruct()
    {
        if ($this->resource !== null) {
            curl_close($this->resource);
        }
    }

    private function init()
    {
        $this->info = $this->getProjectInfo();
        if (!$this->info) {
            throw new \Exception('Request for project info failed.');
        }
    }

    /**
     * @return mixed
     */
    public function getProjectInfo()
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->build();
        $this->request->run();
        $projectInfo = $this->request->getResult();
        return $projectInfo ? $projectInfo->project : null;
    }

    /**
     * @param $key
     * @param $fileId
     * @return mixed|null
     */
    public function getStringId($key, $fileId)
    {
        $params = [
            'filters' => [
                'key' => $key,
                'file' => $fileId
            ]
        ];
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setEndpoint('strings')
            ->setParams($params)
            ->build();
        $this->request->run();
        $result = $this->request->getResult();
        if (!$result) {
            return null;
        }
        return $result[0]->id;
    }

    /**
     * @param array $params
     * @return mixed|null
     */
    public function getProjectStatistics($params = [])
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setParams($params)
            ->setEndpoint('stats')
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param array $params
     * @return mixed|null]
     */
    public function getTopTranslators($params = [])
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setParams($params)
            ->setEndpoint('top_translators')
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $email
     * @param $locale
     * @param $proofread
     * @param string $role
     * @return mixed|null
     */
    public function addUser($email, $locale, $proofread, $role = 'translator')
    {
        $params = array(
            "email" => $email,
            "role" => $role,
            "proofreader" => $proofread,
            "locale" => $locale
        );
        $this->request = $this->builder()
            ->setParams($params)
            ->setEndpoint('users')
            ->setMethod(RequestMethod::POST)
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $localeCode
     * @return bool|mixed|null
     */
    public function addLocale($localeCode)
    {
        $params = array(
            "id" => $localeCode
        );
        $this->request = $this->builder()
            ->setParams($params)
            ->setEndpoint('locales')
            ->setMethod(RequestMethod::POST)
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $file can be name of file or it's unique id
     * @param string $label
     * @param string $locale
     * @throws \Exception
     * @return bool|mixed|null
     */
    public function addString($key, $value, $file, $label = null, $locale = null)
    {
        $params = [
            'key' => $key,
            'type' => 'String',
            'labels' => $label,
            'status' => 'Current',
        ];
        if (is_numeric($file)) {
            $params['file'] = [
                'id' => $file
            ];
        } else {
            $params['file'] = [
                'file_name' => $file
            ];
        }
        if ($value) {
            $locale = $locale ? $locale : $this->info->source_locale->code;
            $params['translations'] = [
                [
                    'locale' => $locale,
                    'text' => $value
                ]
            ];
        }
        $this->request = $this->builder()
            ->setMethod(RequestMethod::POST)
            ->setEndpoint('strings')
            ->setParams($params)
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $stringId
     * @return mixed
     */
    public function deleteString($stringId)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::DELETE)
            ->setEndpoint('strings/' . $stringId)
            ->setJsonEncodeParams(false)
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
    }

    /**
     * @param $stringId
     * @param $locale
     * @param $value
     * @return mixed|null
     */
    public function addTranslate($stringId, $locale, $value)
    {
        $params = [
            'text' => $value
        ];
        $this->request = $this->builder()
            ->setMethod(RequestMethod::POST)
            ->setEndpoint("strings/{$stringId}/locales/{$locale}/translations")
            ->setParams($params)
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $name
     * @param $filePath
     * @return mixed
     */
    public function createFile($name, $filePath)
    {
        $params = [
            'file' => '@' . $filePath,
            'name' => $name,
        ];
        $this->request = $this->builder()
            ->setMethod(RequestMethod::POST)
            ->setParams($params)
            ->setJsonEncodeParams(false)
            ->setEndpoint('files')
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function deleteFile($fileId)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::DELETE)
            ->setEndpoint('files/' . $fileId)
            ->setJsonEncodeParams(false)
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
    }

    /**
     * @param $masterId
     * @param $localeCode
     * @param $name
     * @param $filePath
     * @param bool $merge
     * @param bool $ignoreMissing
     * @param bool $minorChanges
     * @param null $label
     * @return mixed
     */
    public function updateFile($masterId, $localeCode, $name, $filePath, $merge = false, $ignoreMissing = false, $minorChanges = false, $label = null)
    {
        $params = [
            'name' => $name,
            'merge' => $merge,
            'ignore_missing' => $ignoreMissing,
            'minor_changes' => $minorChanges,
            'label' => $label,
            'file' => '@' . $filePath
        ];
        $this->request = $this->builder()
            ->setMethod(RequestMethod::PUT)
            ->setParams($params)
            ->setJsonEncodeParams(false)
            ->setEndpoint("files/{$masterId}/locales/{$localeCode}")
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
    }

    /**
     * @param $params [
     *                  role: can be blank, or one of translator, manager, client. Defaults to blank if left blank.
     *                  filter: can be one of membership, invitation or blank. Defaults to blank if left blank.
     *                  ]
     * @return mixed|null
     */
    public function listUsers($params = [])
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setParams($params)
            ->setEndpoint('users')
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $invitationId
     * @param array $params
     * @return bool|mixed
     *
     * @url https://webtranslateit.com/en/docs/api/user#approve-invitation
     */
    public function approveInvitation($invitationId, $params = [])
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::PUT)
            ->setParams($params)
            ->setEndpoint("users/{$invitationId}/approve")
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $invitation_id
     * @return bool|mixed
     *
     * @url https://webtranslateit.com/en/docs/api/user#remove-invitation
     */
    public function removeInvitation($invitation_id)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::DELETE)
            ->setEndpoint('users/' . $invitation_id)
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->request->getError();
    }

    /**
     * @return WtiRequestBuilder
     */
    private function builder()
    {
        return new WtiRequestBuilder($this->apiKey, $this->resource);
    }

}