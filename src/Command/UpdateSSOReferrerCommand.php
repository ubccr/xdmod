<?php

declare(strict_types=1);

namespace CCR\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'xdmod:update_sso_referrer',
    description: 'Update the sso_referrer value so that XDMoD will know which requests to attempt to authenticate with SimpleSAMLPhp.'
)]
class UpdateSSOReferrerCommand extends Command
{

    public function __construct(
        protected ParameterBagInterface $parameters,
        ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        parent::configure();
        $this->addArgument('url', InputArgument::REQUIRED, 'The url to use to trigger SSO authentication.');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getArgument('url');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $output->writeln("value provided must be a valid url.");
            return Command::INVALID;
        }

        $projectDir = $this->parameters->get('kernel.project_dir');
        $configDir = "$projectDir/config";
        $servicesFilePath = "$configDir/services.yaml";
        $servicesFileContents = Yaml::parseFile($servicesFilePath);

        if (!array_key_exists('parameters', $servicesFileContents)) {
            $output->writeln('Unable to find `parameters` property in services.yaml. Unable to continue.');
            return Command::INVALID;
        }
        if (!array_key_exists('sso', $servicesFileContents['parameters'])) {
            $output->writeln('Unable to find `sso` property in services.yaml. Unable to continue.');
            return Command::INVALID;
        }
        if (!array_key_exists('auth_referrer', $servicesFileContents['parameters']['sso'])) {
            $output->writeln('Unable to find `auth_referrer` property in services.yaml. Unable to continue.');
            return Command::INVALID;
        }

        $servicesFileContents['parameters']['sso']['auth_referrer'] = $url;
        file_put_contents($servicesFilePath, Yaml::dump($servicesFileContents, 10, 4, Yaml::DUMP_OBJECT_AS_MAP ));

        return Command::SUCCESS;
    }
}
