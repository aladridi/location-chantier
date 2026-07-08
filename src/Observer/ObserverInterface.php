<?php
namespace App\Observer;

interface ObserverInterface
{
    public function update(string $event, mixed $data): void;
}