<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'get:secrets')]
final class GetSecretsCommand extends Command
{
    /**
     * The name of the secret to fetch.
     *
     * @var string
     */
    public string $secretName = 'prod/neon-bot/env';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:secrets
                            {--k|key= : Fetch a specific secret by key}
                            {--o|output= : Output the secret as JSON, table, or env}
                            {--s|secret= : Fetch a specific secret by ARN}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch secrets from AWS Secrets Manager';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new SecretsManagerClient([
            'version' => 'latest',
            'region' => config('filesystems.disks.s3.region'),
        ]);

        $secretName = $this->option('secret') ?: $this->secretName;
        $secret = $client->getSecretValue([
            'SecretId' => $secretName,
        ]);

        $secretString = json_decode($secret['SecretString'], true);
        if ($this->option('key')) {
            $this->line($secretString[$this->option('key')]);
            return 0;
        }

        $output = $this->option('output') ?: 'table';

        if ($output === 'json') {
            $this->line(json_encode($secretString, JSON_PRETTY_PRINT));
        } elseif ($output === 'table') {
            // Display the secret as a table of key-value pairs in two columns.
            $this->table(
                ['Key', 'Value'],
                collect($secretString)->map(fn($value, $key) => [$key, $value])->toArray()
            );
        } elseif ($output === 'env') {
            // Display the secret as a series of key-value pairs in the format of a .env file.
            $this->line(collect($secretString)->map(fn($value, $key) => "$key=$value")->implode("\n"));
        }

        return 0;
    }
}
