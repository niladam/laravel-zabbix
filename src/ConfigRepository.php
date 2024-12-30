<?php

namespace Niladam\LaravelZabbix;

class ConfigRepository
{
    protected array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function get(?string $key, $default = null)
    {
        if (is_null($key)) {
            return $this->items;
        }

        return $this->getFromArray($this->items, $key, $default);
    }

    public function all(): array
    {
        return $this->items;
    }

    protected function getFromArray(array $array, string $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
}
