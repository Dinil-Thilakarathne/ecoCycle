<?php

namespace Core\Events;

/**
 * Event Dispatcher Class
 * 
 * Handles event registration, dispatching, and listening.
 * Similar to Laravel's event system functionality.
 * 
 * @package Core\Events
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class EventDispatcher
{
    /**
     * Event listeners
     * 
     * @var array
     */
    protected array $listeners = [];

    /**
     * Wildcard listeners
     * 
     * @var array
     */
    protected array $wildcards = [];

    /**
     * Register event listener
     * 
     * @param string $event
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public function listen(string $event, callable $listener, int $priority = 0): void
    {
        if (strpos($event, '*') !== false) {
            $this->wildcards[$event][] = ['listener' => $listener, 'priority' => $priority];
        } else {
            $this->listeners[$event][] = ['listener' => $listener, 'priority' => $priority];
        }

        // Sort by priority
        if (isset($this->listeners[$event])) {
            usort($this->listeners[$event], function ($a, $b) {
                return $b['priority'] <=> $a['priority'];
            });
        }
    }

    /**
     * Dispatch event to listeners
     * 
     * @param string $event
     * @param array $payload
     * @return array
     */
    public function dispatch(string $event, array $payload = []): array
    {
        $responses = [];

        // Dispatch to specific listeners
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $response = call_user_func($listener['listener'], ...$payload);
                if ($response !== null) {
                    $responses[] = $response;
                }
            }
        }

        // Dispatch to wildcard listeners
        foreach ($this->wildcards as $pattern => $listeners) {
            if ($this->matchesPattern($pattern, $event)) {
                foreach ($listeners as $listener) {
                    $response = call_user_func($listener['listener'], ...$payload);
                    if ($response !== null) {
                        $responses[] = $response;
                    }
                }
            }
        }

        return $responses;
    }

    /**
     * Remove event listeners
     * 
     * @param string $event
     * @return void
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * Check if event has listeners
     * 
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }

    /**
     * Get all listeners for event
     * 
     * @param string $event
     * @return array
     */
    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }

    /**
     * Check if pattern matches event name
     * 
     * @param string $pattern
     * @param string $event
     * @return bool
     */
    protected function matchesPattern(string $pattern, string $event): bool
    {
        $pattern = str_replace('*', '.*', $pattern);
        return preg_match('/^' . $pattern . '$/', $event);
    }

    /**
     * Dispatch event until first non-null response
     * 
     * @param string $event
     * @param array $payload
     * @return mixed
     */
    public function until(string $event, array $payload = [])
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $response = call_user_func($listener['listener'], ...$payload);
                if ($response !== null) {
                    return $response;
                }
            }
        }

        return null;
    }
}

/**
 * Base Event Class
 * 
 * Base class for all events in the system.
 */
abstract class Event
{
    /**
     * Event timestamp
     * 
     * @var int
     */
    public int $timestamp;

    /**
     * Create new event instance
     */
    public function __construct()
    {
        $this->timestamp = time();
    }
}
