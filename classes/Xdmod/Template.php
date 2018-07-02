<?php
/**
 * Template file helper class.
 *
 * Used generating Open XDMoD config files.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace Xdmod;

use Exception;

class Template
{

    /**
     * The name of the template.
     *
     * @var string
     */
    protected $name;

    /**
     * The package the template belongs to.
     *
     * If null, then the template is part of the main Open XDMoD
     * distribution.  Otherwise, this is the name of the Open XDMoD
     * subpackage (i.e. appkernels, supremm).
     *
     * @var null|string
     */
    protected $pkg;

    /**
     * Template file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Template contents.
     *
     * @var string
     */
    protected $contents;

    /**
     * Constructor.
     *
     * @param string $templateName The name of the template.
     * @param string $pkg The package the config file belongs to.
     */
    public function __construct($templateName, $pkg = null)
    {
        $this->name     = $templateName;
        $this->pkg      = $pkg;
        $this->filePath = static::getTemplatePath($templateName, $pkg);
        $this->resetContents();
    }

    /**
     * Get the template file path.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Get the current template contents.
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Reset the contents of the template.
     */
    public function resetContents()
    {
        if (!is_file($this->filePath)) {
            throw new Exception("Template '{$this->filePath}' is not a file");
        }

        $this->contents = @file_get_contents($this->filePath);

        if ($this->contents === false) {
            $msg = "Failed to load template '{$this->name}' contents";
            throw new Exception($msg);
        }
    }

    /**
     * Update template contents.
     *
     * @param array $items Array of key/value pairs to apply to the
     *     template contents.
     */
    public function apply(array $items)
    {
        foreach ($items as $param => $value) {
            if(is_string($param) && is_string($value)) {
                $this->contents = preg_replace(
                    "/\[:$param:\]/",
                    $value,
                    $this->contents
                );
            }
        }
    }

    /**
     * Save the template contents to a file.
     *
     * @param string $destPath Destination file path.
     */
    public function saveTo($destPath)
    {
        $byteCount = file_put_contents($destPath, $this->contents);

        if ($byteCount === false) {
            $msg = "Failed to save template contents to '$destPath'";
            throw new Exception($msg);
        }
    }

    /**
     * Get the template file path.
     *
     * @param string $templateName The name of the template.
     * @param string $pkg The package the config file belongs to.
     *
     * @return string The template file path.
     */
    public static function getTemplatePath($templateName, $pkg = null)
    {
        $dir = static::getTemplateDir();


        $path
            = $pkg === null
            ? sprintf('%s/%s.template', $dir, $templateName)
            : sprintf('%s/%s/%s.template', $dir, $pkg, $templateName);

        return $path;
    }

    /**
     * Get the template directory path.
     *
     * @return string
     */
    public static function getTemplateDir()
    {
        return TEMPLATE_DIR;
    }
}
