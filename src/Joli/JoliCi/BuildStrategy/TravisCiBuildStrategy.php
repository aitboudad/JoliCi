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
use Joli\JoliCi\Builder\DockerfileBuilder;
use Joli\JoliCi\Filesystem\Filesystem;
use Joli\JoliCi\Matrix;
use Joli\JoliCi\Naming;
use Symfony\Component\Yaml\Yaml;

/**
 * TravisCi implementation for build
 *
 * A project must have a .travis.yml file
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class TravisCiBuildStrategy implements BuildStrategyInterface
{
    private $languageVersionKeyMapping = array(
        'ruby' => 'rvm'
    );

    private $defaults = array(
        'php' => array(
            'before_install' => array(),
            'install'        => array('composer install'),
            'before_script'  => array(),
            'script'         => array('phpunit'),
            'env'            => array()
        ),
        'ruby' => array(
            'before_install' => array(),
            'install'        => array('bundle install'),
            'before_script'  => array(),
            'script'         => array('bundle exec rake'),
            'env'            => array()
        ),
        'node_js' => array(
            'before_install' => array(),
            'install'        => array('npm install'),
            'before_script'  => array(),
            'script'         => array('npm test'),
            'env'            => array()
        ),
    );

    /**
     * @var DockerfileBuilder Builder for dockerfile
     */
    private $builder;

    /**
     * @var string Build path for project
     */
    private $buildPath;

    /**
     * @var Filesystem Filesystem service
     */
    private $filesystem;

    /**
     * @var \Joli\JoliCi\Naming Naming service to create docker name for images
     */
    private $naming;

    public function __construct(DockerfileBuilder $builder, $buildPath, Naming $naming, Filesystem $filesystem)
    {
        $this->builder    = $builder;
        $this->buildPath  = $buildPath;
        $this->naming     = $naming;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilds($directory)
    {
        $builds     = array();
        $config     = Yaml::parse($directory.DIRECTORY_SEPARATOR.".travis.yml");
        $matrix     = $this->createMatrix($config);
        $timezone   = ini_get('date.timezone');

        foreach ($matrix->compute() as $possibility) {
            $name = $this->naming->getName($directory, $this->getName(), $possibility);

            $this->builder->setTemplateName(sprintf("%s/Dockerfile-%s.twig", $possibility['language'], $possibility['version']));
            $this->builder->setVariables(array(
                'before_install' => $possibility['before_install'],
                'install'        => $possibility['install'],
                'before_script'  => $possibility['before_script'],
                'script'         => $possibility['script'],
                'env'            => $possibility['environment'],
                'timezone'       => $timezone
            ));

            $buildDir  = $this->buildPath . DIRECTORY_SEPARATOR . $name;

            // Remove existing dir if exist
            if ($this->filesystem->exists($buildDir)) {
                $this->filesystem->remove($buildDir);
            }

            // Recursive copy of the pull to this directory
            $this->filesystem->rcopy($directory, $buildDir, true);

            $this->builder->setOutputName('Dockerfile');

            try {
                $this->builder->writeOnDisk($buildDir);
            } catch (\Twig_Error_Loader $e) {
                $this->filesystem->remove($buildDir);
            }

            $description = sprintf('%s = %s', $possibility['language'], $possibility['version']);

            if ($possibility['environment'] !== null) {
                $description .= sprintf(', Environment: %s', json_encode($possibility['environment']));
            }

            $builds[] = new Build($name, $buildDir, $description);
        }

        return $builds;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "travisci";
    }

    /**
     * {@inheritdoc}
     */
    public function supportProject($directory)
    {
        return file_exists($directory.DIRECTORY_SEPARATOR.".travis.yml") && is_file($directory.DIRECTORY_SEPARATOR.".travis.yml");
    }

    /**
     * Get command lines to add for a configuration value in .travis.yml file
     *
     * @param array  $config   Configuration of travis ci parsed
     * @param string $language Language for getting the default value if no value is set
     * @param string $key      Configuration key
     *
     * @return array A list of command to add to Dockerfile
     */
    private function getConfigValue($config, $language, $key)
    {
        if (!isset($config[$key]) || empty($config[$key])) {
            if (isset($this->defaults[$language][$key])) {
                return $this->defaults[$language][$key];
            }

            return array();
        }

        if (!is_array($config[$key])) {
            return array($config[$key]);
        }

        return $config[$key];
    }

    /**
     * Create matrix of build
     *
     * @param array $config
     *
     * @return Matrix
     */
    protected function createMatrix($config)
    {
        $language       = isset($config['language']) ? $config['language'] : 'ruby';
        $versionKey     = isset($this->languageVersionKeyMapping[$language]) ? $this->languageVersionKeyMapping[$language] : $language;
        $envFromConfig  = $this->getConfigValue($config, $language, "env");
        $environnements = array();

        // Parsing environnements
        foreach ($envFromConfig as $envLine) {
            $envVars     = explode(' ', $envLine ? : '');
            $environment = array();

            foreach ($envVars as $env) {
                if (!empty($env)) {
                    list($key, $value) = explode('=', $env);
                    $environment[$key] = $value;
                }
            }

            $environnements[] = $environment;
        }

        $matrix = new Matrix();
        $matrix->setDimension('language', array($language));
        $matrix->setDimension('environment', $environnements);
        $matrix->setDimension('version', $config[$versionKey]);
        $matrix->setDimension('before_install', array($this->getConfigValue($config, $language, 'before_install')));
        $matrix->setDimension('install', array($this->getConfigValue($config, $language, 'install')));
        $matrix->setDimension('before_script', array($this->getConfigValue($config, $language, 'before_script')));
        $matrix->setDimension('script', array($this->getConfigValue($config, $language, 'script')));

        return $matrix;
    }
}
