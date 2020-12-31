<?php


namespace Laravel\Vapor\Runtime;


class CustomSecrets
{
    /**
     * Add all of the secret parameters at the given path to the environment.
     *
     * @param string $path
     * @param array|null $parameters
     * @param string $file
     * @return array
     */
    public static function fromFile(string $fullPath)
    {
        $parameters = require $fullPath;

        $secrets = [];

        foreach ($parameters as $name => $value) {
            echo "Injecting secret [{$name}] into runtime." . PHP_EOL;

            $_ENV[$name] = $value;
            $secrets[$name] = $value;
        }

        return $secrets;
    }
}

