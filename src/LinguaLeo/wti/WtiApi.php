<?php

namespace LinguaLeo\wti;

use LinguaLeo\wti\Exception\WtiApiException;

class WtiApi
{

    /** @var object */
    private $info;
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
        $this->init($initProjectInfo);
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
    private function init($initProjectInfo)
    {
        if ($initProjectInfo && !$this->getProjectInfo()) {
            throw new WtiApiException('Request for project info failed.');
        }
    }

    /**
     * @return mixed
     */
    public function getProjectInfo()
    {
        if ($this->info) {
            return $this->info;
        }
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->build();
        $this->request->run();
        $projectInfo = $this->request->getResult();
        if ($projectInfo) {
            $this->info = $projectInfo->project;
            return $this->info;
        }
        return null;
    }

    /**
     * @param $masterFileId
     * @return bool
     */
    public function isMasterFileExists($masterFileId)
    {
        foreach ($this->getProjectInfo()->project_files as $projectFile) {
            if ($projectFile->master_project_file_id !== null) {
                continue;
            }
            if (is_numeric($masterFileId) && ($projectFile->id === (int)$masterFileId)) {
                return true;
            } elseif ($projectFile->name === $masterFileId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $filename
     * @return int|null
     */
    public function getFileIdByName($filename)
    {
        $info = $this->getProjectInfo();
        foreach ($info->project_files as $file) {
            if ($file->name == $filename) {
                return $file->id;
            }
        }
        return null;
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
                'key' => $key
            ]
        ];
        if (is_numeric($fileId)) {
            $params['filters']['file'] = $fileId;
        } else {
            $params['filters']['file_name'] = $fileId;
        }

        return $this->listStrings($params);
    }

    public function listStrings($params = null, $page = 1)
    {
        if ($params == null) {
            $params = [];
        }
        $params['page'] = intval($page);
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setEndpoint('strings')
            ->setParams($params)
            ->build();
        $this->request->run();
        $requestResult = $this->request->getResult();
        $result = $requestResult ? $requestResult : [];

        if (count($result) && $this->isRequestHasNextPage()) {
            $result += $this->listStrings($params, ++$page);
        }

        return $result;
    }

    private function isRequestHasNextPage()
    {
        $responseHeaders = $this->request->getHeaders();
        if (isset($responseHeaders['Link'])) {
            return true;
        }
        return false;
    }

    /**
     * Get translation for stringId and locale
     *
     * @param $stringId
     * @param $localeCode
     * @return mixed|null
     */
    public function getTranslation($stringId, $localeCode)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::GET)
            ->setEndpoint('strings/' . $stringId . '/locales/' . $localeCode . '/translations')
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $key
     * @param $fileId
     * @return mixed|null
     */
    public function getStringId($key, $fileId)
    {
        $result = $this->getStringsByKey($key, $fileId);
        if (!$result) {
            return null;
        }
        foreach ($result as $string) {
            if ($string->key == $key) {
                return $string->id;
            }
        }
        return null;
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
            'email' => $email,
            'role' => $role,
            'proofreader' => $proofread,
            'locale' => $locale
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
            'id' => $localeCode
        );
        $this->request = $this->builder()
            ->setParams($params)
            ->setEndpoint('locales')
            ->setMethod(RequestMethod::POST)
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
    }

    /**
     * @param $localeCode
     * @return mixed
     */
    public function deleteLocale($localeCode)
    {
        $this->request = $this->builder()
            ->setEndpoint('locales/' . $localeCode)
            ->setJsonEncodeParams(false)
            ->setMethod(RequestMethod::DELETE)
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
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
        $translations = [];

        if ($value) {
            $locale = $locale ? $locale : $this->getProjectInfo()->source_locale->code;
            $translations = [
                [
                    'locale' => $locale,
                    'text' => $value
                ]
            ];
        }
        return $this->addStringWithTranslations($key, $file, $translations, $label, $type);
    }

    /**
     * @param string $key
     * @param string $file can be name of file or it's unique id
     * @param string $label
     * @param array $translations [ [locale, text, status] ]
     * @param string $type
     * @return bool|mixed|null
     */
    public function addStringWithTranslations($key, $file, $translations, $label = null, $type = Type::TYPE_STRING)
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
        $params['translations'] = $translations;
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
        if ($value === null) {
            $value = '';
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
     * @param string $mime
     * @return mixed
     */
    public function createFile($name, $filePath, $mime = 'application/json')
    {
        $params = [
            'file' => new \CURLFile($filePath, $mime, $name),
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
     * @param $masterFileId
     * @param $localeCode
     * @param $name
     * @param $filePath
     * @param bool $merge
     * @param bool $ignoreMissing
     * @param bool $minorChanges
     * @param null $label
     * @param string $mime
     * @return mixed
     */
    public function updateFile(
        $masterFileId,
        $localeCode,
        $name,
        $filePath,
        $merge = false,
        $ignoreMissing = false,
        $minorChanges = false,
        $label = null,
        $mime = 'application/json'
    )
    {
        $params = [
            'name' => $name,
            'merge' => $merge,
            'ignore_missing' => $ignoreMissing,
            'minor_changes' => $minorChanges,
            'label' => $label,
            'file' => new \CURLFile($filePath, $mime, $name)
        ];
        $this->request = $this->builder()
            ->setMethod(RequestMethod::PUT)
            ->setParams($params)
            ->setJsonEncodeParams(false)
            ->setEndpoint("files/{$masterFileId}/locales/{$localeCode}")
            ->build();
        $this->request->run();
        return $this->request->getRawResult();
    }

    /**
     * @deprecated
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
     * @param int $membershipId
     * @param array $params
     * @return mixed|null
     */
    public function updateMembership($membershipId, array $params = [])
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::PUT)
            ->setEndpoint('memberships/' . $membershipId)
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
     * @param $invitationId
     * @return bool|mixed
     */
    public function removeInvitation($invitationId)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::DELETE)
            ->setEndpoint('users/' . $invitationId)
            ->build();
        $this->request->run();
        return $this->request->getResult();
    }

    /**
     * @param $userId
     * @return bool|mixed
     */
    public function removeMembership($userId)
    {
        $this->request = $this->builder()
            ->setMethod(RequestMethod::DELETE)
            ->setEndpoint('memberships/' . $userId)
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
