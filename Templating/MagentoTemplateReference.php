<?php
namespace Liip\MagentoBundle\Templating;
use \Symfony\Component\Templating\TemplateReferenceInterface;

class MagentoTemplateReference implements TemplateReferenceInterface
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function all()
    {
        return array();
    }

    public function set($name, $value)
    {
    }

    public function get($name)
    {
        return null;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLogicalName()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->path;
    }
}
