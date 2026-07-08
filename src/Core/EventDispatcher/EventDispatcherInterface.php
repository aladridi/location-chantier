<?php
namespace App\Core\EventDispatcher;

interface EventDispatcherInterface
{
    public function dispatch(string $eventName, mixed $data = null): void;
    public function addListener(string $eventName, callable $listener): void;
}