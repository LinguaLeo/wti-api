<?php
namespace LinguaLeo\wti\impl;

use LinguaLeo\wti\WtiParser;

class PhpTemplateParser extends WtiParser
{
    public function replaceStringWithKey($string, $key)
    {
        $this->content = str_replace('@' . $string . '@', $this->getKeyTemplate($key), $this->content);

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
        return "<?php echo __('$key', null, '" . pathinfo($this->langFilename, PATHINFO_FILENAME) . "'); ?>";
    }
}