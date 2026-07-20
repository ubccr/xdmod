<?php

namespace CCR\Helper;

use CCR\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\FormatException;
use Symfony\Component\Dotenv\Exception\PathException;

/**
 * The purpose of this class is to serve as a bridge to allow our existing code in `classes` to execute Symfony Commands
 * without relying on `exec`ing php scripts.
 *
 * NOTE: Once we have all of our existing code migrated to `src` then we can change `executeCommand` from a static
 * function to a regular function and use Dependency Injection to add it to the classes that need it.
 */
readonly class SymfonyCommandHelper
{

    public function __construct(private ?LoggerInterface $logger = null) {
    }

    /**
     * Execute the provided Symfony $command.
     *
     * @param string $command the Symfony command to run.
     * @throws \LogicException if the command is empty.
     * @throws \Exception if an error is encountered while running the specified command.
     * @throws \RuntimeException if a non-zero exit code is returned by the Symfony Command.
     */
    public function executeCommand(string $command, string $env = 'prod', bool $debug = false): string
    {
        $this->logger?->debug('Executing Symfony Command', ['command' => $command]);

        if (empty($command)) {
            throw new \LogicException('Command must not be empty.');
        }

        // Make sure that whatever command is being executed correctly utilizes any .env variables.
        try {
            $envPath = BASE_DIR . '/.env';
            (new Dotenv())->bootEnv($envPath);
        } catch(FormatException | PathException $e) {
            throw new \RuntimeException('Unable to load environment file', $e->getCode(), $e);
        }

        // Setup our Kernel / Application
        $kernel = new Kernel($env, $debug);
        $application = new Application($kernel);

        // we set this so that it doesn't `exit` whatever php script is calling this function.
        $application->setAutoExit(false);

        $input = new StringInput($command);
        $output = new BufferedOutput();

        $statusCode = $application->run($input, $output);
        $this->logger?->debug('Symfony Command Executed', ['status_code' => $statusCode]);

        if ($statusCode !== 0) {
            throw new \RuntimeException("Error Running Symfony Command $command");
        }

        return $output->fetch();
    }

    /**
     * A helper function that first executes cache:clear followed by dotenv:dump. This ensures that we have an up to
     * date `.env.local.php` file.
     *
     * @return void
     * @throws \Exception
     */
    public function dumpDotEnv(): void
    {
        // Make sure to clear the cache before dumping the dotenv so we start clean.
        $this->executeCommand('cache:clear');

        // Dump dotenv data so we don't read .env each time in prod.
        // Note: this means that if you want to start debugging stuff you'll need to delete or modify the generated
        // .env.local.php.
        $this->executeCommand('dotenv:dump');
    }
}
