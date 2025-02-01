<?php

namespace App\Commands;

use App\Service\DiagramGenerator;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->addArgument('path', InputArgument::REQUIRED, 'source root to analyze');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = new OllamaConfig();
        $config->model = 'mistral:latest';
        $chat = new OllamaChat($config);
        $response = $this->diagramGenerator->generate($chat, $config->model, $output, $input->getArgument('path'));

        $output->writeln($response);
        return Command::SUCCESS;
    }
}
