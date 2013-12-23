<?php

namespace LinguaLeo\wti\Parser;

class TwigTemplateParser extends Parser
{

    public function replaceStringWithKey($string, $key)
    {
        $this->content = str_replace("@{$string}@", $this->getKeyTemplate($key), $this->content);
        return $this;
    }

    public function extractStringList()
    {
        preg_match_all(
            "/[^\"\']\@([^@]*)\@/ms",
            $this->content,
            $out
        );
        return $out[1];
    }

    public function getKeyPrefix()
    {
        return pathinfo($this->langFilename, PATHINFO_FILENAME) . '/';
    }

    public function getKeyTemplate($key)
    {
        $path = pathinfo($this->langFilename, PATHINFO_FILENAME);
        return "{% _ '$key', '$path' %}";
    }

}