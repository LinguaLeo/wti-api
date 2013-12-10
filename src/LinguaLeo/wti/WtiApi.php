<?php

namespace LinguaLeo\wti;

use LinguaLeo\wti\Exception\WtiApiException;

class WtiApi
{

    public $info;
    /** @var string */
    private $apiKey;
    /** @var resource  */
    private $resource = null;
    /** @var WtiApiRequest */
    private $request;

    /**
     * @param string $apiKey
     * @param bool $initProjectInfo
     * @throws Exception\WtiApiException
     */
    public function __construct($apiKey, $initProjectInfo = true)
    {
        if (!$apiKey) {
            throw new WtiApiException('Wti API key must be setup before use.');
        }
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

    /**
     * @throws Exception\WtiApiException
     */
    private function init()
    {
        $this->info = $this->getProjectInfo();
        if (!$this->info) {
            throw new WtiApiException('Request for project info failed.');
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
     * @param $masterFileId
     * @return bool
     */
    public function isMasterFileExists($masterFileId)
    {
        if (!$this->info) {
            $this->init();
        }
        foreach ($this->info->project_files as $projectFile) {
            if ($projectFile->id === (int)$masterFileId && $projectFile->master_project_file_id === null) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $key
     * @param $fileId
     * @return mixed|null
     */
    public function getStringsByKey($key, $fileId)
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
        return $result ? $result : [];
    }

    /**
     * @param $key
     * @param $fileId
     * @return mixed|null
     */
    public function getStringId($key, $fileId)
    {
        $result = $this->getStringsByKey($key, $fileId);
        return $result ? $result[0]->id : null;
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
     * @param string $type
     * @return bool|mixed|null
     */
    public function addString($key, $value, $file, $label = null, $locale = null, $type = Type::TYPE_STRING)
    {
        $params = [
            'key' => $key,
            'type' => $type,
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
     * @param string $status
     * @return mixed|null
     */
    public function addTranslate($stringId, $locale, $value, $status = Status::UNVERIFIED)
    {
        if (!$value) {
            return null;
        }
        $params = [
            'text' => $value,
            'status' => $status,
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
     * @param string $filename
     * @param string $ext
     * @throws Exception\WtiApiException
     * @return int
     */
    public function createEmptyFile($filename, $ext = 'json')
    {
        $path = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), uniqid() . '.' . $ext]);
        file_put_contents($path, json_encode([], JSON_FORCE_OBJECT));
        $masterId = (int)$this->createFile($filename, $path);
        if (!$masterId) {
            throw new WtiApiException('Cannot create master file with filename' . $filename);
        }
        return $masterId;
    }

    /**
     * @param $fileId
     * @param $locale
     * @return mixed|null
     */
    public function loadFile($fileId, $locale)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setEndpoint("files/{$fileId}/locales/{$locale}")
            ->setIsJsonToEndpointAdded(false)
            ->build();
        $this->request->run();
        return $this->request->getResult(true);
    }

    /**
     * @param int $stringId
     * @param string $label
     * @return mixed|null
     */
    public function updateStringLabel($stringId, $label)
    {
        $params = [
            'labels' => $label
        ];
        $this->request = $this->builder()
            ->setMethod(RequestMethod::PUT)
            ->setEndpoint('strings/' . $stringId)
            ->setParams($params)
            ->build();
        $this->request->run();
        return $this->request->getResult();
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