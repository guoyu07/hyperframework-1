<?php
namespace Hyperframework\Common;

class EventEmitter {
    /**
     * @param object $listener
     * @return void
     */
    public static function addListener($listener) {
        $engine = static::getEngine();
        foreach ($listener->getEventBindings() as $name => $method) {
            $engine->bind($name, [$listener, $method]);
        }
    }

    /**
     * @param object $listener
     * @return void
     */
    public static function removeListener($listener) {
        $engine = static::getEngine();
        foreach ($listener->getEventBindings() as $name => $method) {
            $engine->unbind($name, [$listener, $method]);
        }
    }

    /**
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public static function bind($name, $callback) {
        static::getEngine()->bind($name, $callback);
    }

    /**
     * @param array $bindings
     * @return void
     */
    public static function bindAll($bindings) {
        $engine = static::getEngine();
        foreach ($bindings as $name => $callback) {
            $engine->bind($name, $callback);
        }
    }

    /**
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public static function unbind($name, $callback) {
        static::getEngine()->unbind($name, $callback);
    }

    /**
     * @param array $bindings
     * @return void
     */
    public static function unbindAll($bindings) {
        $engine = static::getEngine();
        foreach ($bindings as $name => $callback) {
            $engine->unbind($name, $callback);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public static function emit($name, $arguments = []) {
        static::getEngine()->emit($name, $arguments);
    }

    /**
     * @return EventEmitterEngine
     */
    public static function getEngine() {
        return Registry::get('hyperframework.event_emitter_engine', function() {
            $class = Config::getClass(
                'hyperframework.event_emitter_engine_class',
                EventEmitterEngine::class
            );
            return new $class;
        });
    }
}
