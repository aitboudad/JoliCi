<?php

namespace Joli\JoliCi;

use Behat\Transliterator\Transliterator;

class Naming
{
    const BASE_NAME = 'jolici';

    /**
     * Get repository name for docker images build with this strategy
     *
     * @param string $projectPath  Project directory
     * @param string $strategyName Strategy name
     *
     * @return string
     */
    public function getRepository($projectPath, $strategyName)
    {
        $project = basename(realpath($projectPath));
        $project = Transliterator::transliterate($project, '-');

        return sprintf('%s_%s/%s', static::BASE_NAME, $strategyName, $project);
    }

    /**
     * Generate the tag name for a docker image
     *
     * @param array $parameters Parameters which make the build instance unique
     *
     * @return string
     */
    public function getTag($parameters = array())
    {
        $date = new \DateTime();

        return sprintf('%s-%s', crc32(serialize($parameters)), $date->format('U'));
    }

    /**
     * Return the full name for a docker image given is path and its unique parameters
     *
     * @param string $projectPath  Project directory
     * @param string $strategyName Strategy name
     * @param array  $parameters   Parameters which make the build instance unique
     *
     * @return string
     */
    public function getName($projectPath, $strategyName, $parameters = array())
    {
        return sprintf('%s:%s', $this->getRepository($projectPath, $strategyName), $this->getTag($parameters));
    }
}
