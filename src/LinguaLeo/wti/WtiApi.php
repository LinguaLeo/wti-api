<?php
namespace LinguaLeo\wti;

class WtiApi
{

    public $info;
    private $apiKey;

    /** @var WtiApiRequest */
    private $request;

    public function __construct($apiKey, $initProjectInfo = true)
    {
        $this->apiKey = $apiKey;
        if ($initProjectInfo) {
            $this->init();
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
     * @param $key
     * @param $value
     * @param $filename
     * @return bool|mixed|null
     * @throws \Exception
     */
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
        $this->request = $this->builder()
            ->setMethod(RequestMethod::POST)
            ->setEndpoint('strings')
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
        return new WtiRequestBuilder($this->apiKey);
    }

}