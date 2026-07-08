<?php
namespace App\Observer;

abstract class AbstractObserver implements ObserverInterface
{
    protected function log(string $message): void
    {
        error_log(sprintf('[%s] %s', date('Y-m-d H:i:s'), $message));
    }
}