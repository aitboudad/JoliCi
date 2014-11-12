<?php
/*
 * This file is part of JoliCi.
*
* (c) Joel Wurtz <jwurtz@jolicode.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Joli\JoliCi\BuildStrategy;

use Joli\JoliCi\Build;
use Joli\JoliCi\Filesystem\Filesystem;
use Joli\JoliCi\Naming;
use Symfony\Component\Finder\Finder;

/**
 * JoliCi implementation for build
 *
 * A project must have a .jolici directory, each directory inside this one will be a type of build and must contain a Dockerfile to be executable
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class JoliCiBuildStrategy implements BuildStrategyInterface
{
    /**
     * @var string Base path for build
     */
    private $buildPath;

    /**
     * @var Filesystem Filesystem service
     */
    private $filesystem;

    /**
     * @var Naming Use to name the image created
     */
    private $naming;

    public function __construct($buildPath, Naming $naming, Filesystem $filesystem)
    {
        $this->buildPath  = $buildPath;
        $this->naming     = $naming;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilds($directory)
    {
        $builds = array();
        $finder = new Finder();
        $finder->directories();

        foreach ($finder->in($this->getJoliCiStrategyDirectory($directory)) as $dir) {
            $name      = $this->naming->getName($directory, $this->getName(), array('build' => $dir->getFilename()));
            $buildDir  = $this->buildPath . DIRECTORY_SEPARATOR . $name;

            //Recursive copy of the pull to this directory
            $this->filesystem->rcopy($directory, $buildDir, true);

            //Recursive copy of content of the build dir to the root dir
            $this->filesystem->rcopy($dir->getRealPath(), $buildDir, true);

            $builds[] = new Build($name, $buildDir, "JoliCi Strategy Build : " . $dir->getFilename());
        }

        return $builds;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "jolici";
    }

    /**
     * {@inheritdoc}
     */
    public function supportProject($directory)
    {
        return file_exists($this->getJoliCiStrategyDirectory($directory)) && is_dir($this->getJoliCiStrategyDirectory($directory));
    }

    /**
     *
     *
     * @param $projectPath
     * @return string
     */
    protected function getJoliCiStrategyDirectory($projectPath)
    {
        return $projectPath . DIRECTORY_SEPARATOR . '.jolici';
    }
}
