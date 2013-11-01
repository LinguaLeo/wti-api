<?php
namespace LinguaLeo\wti;

abstract class WtiParser
{

    protected $filename;
    protected $langFilename;
    protected $originalContent;
    protected $stringsList;
    protected $keysList = array();

    public function __construct($filename, $langFilename)
    {
        $this->filename = $filename;
        $this->langFilename = $langFilename;
        $this->content = $this->originalContent = file_get_contents($this->filename);
    }

    public function getStringsList()
    {
        if (is_null($this->stringsList)) {
            $this->stringsList = $this->extractStringList();
        }

        return $this->stringsList;
    }

    public function addKeyForString($string, $key)
    {
        $this->keysList[$string] = $key;

        return $this;
    }

    public function getKeysList()
    {
        return $this->keysList;
    }

    public function replaceAllKeys()
    {
        foreach ($this->getKeysList() as $string => $key) {
            $this->replaceStringWithKey($string, $key);
        }

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function save()
    {
        file_put_contents($this->filename, $this->content);
    }


    abstract public function extractStringList();

    abstract public function replaceStringWithKey($string, $key);

    abstract public function getKeyPrefix();

    abstract public function getKeyTemplate($key);

}