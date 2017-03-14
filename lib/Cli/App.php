<?php
namespace Hyperframework\Cli;

use Hyperframework\Common\Config;
use Hyperframework\Common\ClassNotFoundException;
use Hyperframework\Common\App as Base;

class App extends Base {
    private $commandConfig;
    private $options = [];
    private $arguments = [];

    /**
     * @param string $rootPath
     * @return void
     */
    public static function run($rootPath) {
        $app = static::createApp($rootPath);
        $app->executeCommand();
        $app->finalize();
    }

    /**
     * @param string $rootPath
     */
    public function __construct($rootPath) {
        parent::__construct($rootPath);
        $this->initializeCommandOptionsAndArguments();
    }

    /**
     * @return string[]
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption($name) {
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getOption($name) {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
    }

    /**
     * @return string[]
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @return CommandConfig
     */
    public function getCommandConfig() {
        if ($this->commandConfig === null) {
            $class = Config::getClass(
                'hyperframework.cli.command_config_class', CommandConfig::class
            );
            $this->commandConfig = new $class;
        }
        return $this->commandConfig;
    }

    /**
     * @param string $rootPath
     * @return static
     */
    protected static function createApp($rootPath) {
        return new static($rootPath);
    }

    /**
     * @return void
     */
    protected function initializeCommandOptionsAndArguments() {
        $elements = $this->parseCommand();
        if (isset($elements['options'])) {
            $this->setOptions($elements['options']);
        }
        if (isset($elements['arguments'])) {
            $this->setArguments($elements['arguments']);
        }
        if ($this->hasOption('help')) {
            $this->renderHelp();
            $this->quit();
        }
        if ($this->hasOption('version')) {
            $this->renderVersion();
            $this->quit();
        }
    }

    /**
     * @param string[] $options
     * @return void
     */
    protected function setOptions($options) {
        $this->options = $options;
    }

    /**
     * @param string[] $arguments
     * @return void
     */
    protected function setArguments($arguments) {
        $this->arguments = $arguments;
    }

    /**
     * @return void
     */
    protected function executeCommand() {
        $commandConfig = $this->getCommandConfig();
        $class = $commandConfig->getClass();
        if (class_exists($class) === false) {
            throw new ClassNotFoundException(
                "Command class '$class' does not exist."
            );
        }
        $command = new $class($this);
        $arguments = $this->getArguments();
        call_user_func_array([$command, 'execute'], $arguments);
    }

    /**
     * @return void
     */
    protected function renderHelp() {
        $class = Config::getClass(
            'hyperframework.cli.help_class', Help::class
        );
        $help = new $class($this);
        $help->render();
    }

    /**
     * @return void
     */
    protected function renderVersion() {
        $commandConfig = $this->getCommandConfig();
        $version = (string)$commandConfig->getVersion();
        if ($version === '') {
            echo 'undefined', PHP_EOL;
            return;
        }
        echo $version, PHP_EOL;
    }

    /**
     * @return array
     */
    protected function parseCommand() {
        try {
            $class = Config::getClass(
                'hyperframework.cli.command_parser_class', CommandParser::class
            );
            $commandConfig = $this->getCommandConfig();
            $commandParser = new $class;
            return $commandParser->parse($commandConfig);
        } catch (CommandParsingException $e) {
            $this->renderCommandParsingError($e);
            $this->quit();
        }
    }

    /**
     * @param CommandParsingException $commandParsingException
     * @return void
     */
    protected function renderCommandParsingError($commandParsingException) {
        echo $commandParsingException->getMessage(), PHP_EOL;
        $config = $this->getCommandConfig();
        $name = $config->getName();
        $subcommandName = $commandParsingException->getSubcommandName();
        if ($subcommandName !== null) {
            $name .= ' ' . $subcommandName;
        }
        if ($config->getOptionConfig('help', $subcommandName) !== null) {
            echo 'See \'', $name, ' --help\'.', PHP_EOL;
        }
    }
}