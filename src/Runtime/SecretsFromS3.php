<?php


namespace Laravel\Vapor\Runtime;


use Aws\S3\S3Client;

class SecretsFromS3
{
    /**
     * Add all of the secret parameters at the given path to the environment.
     *
     * @param string $path
     * @param array|null $parameters
     * @param string $file
     * @return array
     */
    public static function addToEnvironment(string $envType, string $appName)
    {
        echo "Building [{$appName}] [{$envType}] environment." . PHP_EOL;

        $s3Client = new S3Client($args = [
            "version" => "latest",
            "credentials" => [
                "key" => $_ENV['S3_SECRETS_KEY'],
                "secret" => $_ENV['S3_SECRETS_SECRET'],
            ],
            "region" => $_ENV['S3_SECRETS_REGION'],
            "bucket" => $_ENV['S3_SECRETS_BUCKET'],
        ]);

        $envFiles = [
            "$envType-common-secrets.env" => __DIR__ . "/$envType-common-secrets.env",
            "$envType-common-vars.env" => __DIR__ . "/$envType-common-vars.env",
            "$envType-$appName-secrets.env" => __DIR__ . "/$envType-$appName-secrets.env",
            "$envType-$appName-vars.env" => __DIR__ . "/$envType-$appName-vars.env",
        ];

        $secrets = [];

        foreach ($envFiles as $s3Key => $localPath) {
            echo "Fetching [{$s3Key}] from S3." . PHP_EOL;

            $s3Client->getObject([
                'Bucket' => $args['bucket'],
                'Key' => $s3Key,
                'SaveAs' => $localPath,
            ]);

            foreach (self::parseSecrets($localPath) as $name => $value) {
                echo "Injecting secret [{$name}] into runtime.".PHP_EOL;

                $_ENV[$name] = $value;
                $secrets[$name] = $value;
            }
        }

        return $secrets;
    }

    private static function parseSecrets(string $localPath): array
    {
        return collect(file($localPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->mapWithKeys(function ($line) {
                if (strpos(trim($line), '#') === 0) {
                    return null;
                }

                [$name, $value] = explode('=', $line, 2);

                return [
                    trim($name) => trim($value)
                ];
            })->filter()->values()->all();
    }
}

