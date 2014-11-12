<?php
/*
 * This file is part of JoliCi.
*
* (c) Joel Wurtz <jwurtz@jolicode.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Joli\JoliCi;

class Build
{
    /**
     * @var string Name of build
     */
    protected $name;

    /**
     * @var string Description of this build (generally a nice name for end user)
     */
    protected $description;

    /**
     * @var string Path of build
     */
    protected $directory;

    /**
     * @param string $name        Name of the build
     * @param string $directory   Directory where the build
     * @param string $description Description of this build (generally a nice name for end user)
     */
    public function __construct($name, $directory, $description = "")
    {
        $this->name        = $name;
        $this->directory   = $directory;
        $this->description = $description;
    }

    /**
     * Get name of this build
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return directory of build
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}