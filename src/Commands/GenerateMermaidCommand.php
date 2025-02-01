<?php

namespace App\Commands;

use App\Service\DiagramGenerator;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:generate-mermaid')]
class GenerateMermaidCommand extends Command
{
    public function __construct(private readonly DiagramGenerator $diagramGenerator)
    {
        parent::__construct('app:test');
    }
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'source root to analyze')
            ->addOption('model', 'm',InputOption::VALUE_OPTIONAL, 'model to be used', 'mistral:latest')
            ->addOption('ollamaUrl', 'u', InputOption::VALUE_OPTIONAL, 'ollama URL', 'http://localhost:11434/api/')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = new OllamaConfig();
        $config->model = $input->getOption('model');
        $config->url = $input->getOption('ollamaUrl');
        $chat = new OllamaChat($config);
        $output->writeln(sprintf('<info>Generating mermaid</info>'));
        $output->writeln(sprintf('<info>Using model</info>: <comment>%s</comment>', $config->model));
        $output->writeln(sprintf('<info>on Url</info>: <comment>%s</comment>', $config->url));
        $response = $this->diagramGenerator->generate($chat, $config->model, $output, $input->getArgument('path'));

        $output->writeln($response);
        return Command::SUCCESS;
    }
}
