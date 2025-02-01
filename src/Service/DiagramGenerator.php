<?php

namespace App\Service;

use LLPhant\Chat\ChatInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\ItemInterface;

# This is our processing. This is where GitDiagram makes the magic happen
# There is a lot of DETAIL we need to extract from the repository to produce detailed and accurate diagrams
# I will immediately put out there that I'm trying to reduce costs. Theoretically, I could, for like 5x better accuracy, include most file content as well which would make for perfect diagrams, but thats too many tokens for my wallet, and would probably greatly increase generation time. (maybe a paid feature?)

# THE PROCESS:

# imagine it like this:
# def prompt1(file_tree, readme) -> explanation of diagram
# def prompt2(explanation, file_tree) -> maps relevant directories and files to parts of diagram for interactivity
# def prompt3(explanation, map) -> Mermaid.js code

# Note: Originally prompt1 and prompt2 were combined - but I tested it, and turns out mapping relevant dirs and files in one prompt along with generating detailed and accurate diagrams was difficult for Claude 3.5 Sonnet. It lost detail in the explanation and dedicated more "effort" to the mappings, so this is now its own prompt.

# This is my first take at prompt engineering so if you have any ideas on optimizations please make an issue on the GitHub!

class DiagramGenerator
{
    private FilesystemAdapter $cache;
    private const excludedPathPatterns = [
        "/node_modules/",
        "/vendor/",
        "/venv/",
        "/__pycache__/",
    ];
    private const excludedFilePatterns = [
        "/.*\.min\./",
        "/.*\.pyc/",
        "/.*\.pyo/",
        "/.*\.pyd/",
        "/.*\.so/",
        "/.*\.dll/",
        "/.*\.class/",
        "/.*\.jpg/",
        "/.*\.jpeg/",
        "/.*\.png/",
        "/.*\.gif/",
        "/.*\.ico/",
        "/.*\.svg/",
        "/.*\.ttf/",
        "/.*\.woff/",
        "/.*\.webp/",
        "/.*\.cache/",
        "/.*\.tmp/",
        "/yarn\.lock/",
        "/poetry\.lock/",
        "/.*\.log/",
        "/.*\.vscode/",
        "/.*\.idea/",
    ];

    public function __construct()
    {
        $this->cache = new FilesystemAdapter(directory:'/cache');
    }

    public function generate(ChatInterface $chat, string $model, OutputInterface $output, string $path): string
    {
        # def prompt1(file_tree, readme) -> explanation of diagram
        # def prompt2(explanation, file_tree) -> maps relevant directories and files to parts of diagram for interactivity
        # def prompt3(explanation, map) -> Mermaid.js code

        $fileList = $this->generateFileList($path);
        $readme = $this->getReadme($path);
        $output->writeln(sprintf("Generating explanation from files list (%d) and readme (%d)",count($fileList),strlen($readme)));
        $explanation = $this->generateChatText(
            $chat,
            $model,
            implode("\n", [
                file_get_contents(__DIR__ . '/prompts/explanationInstructions.txt'),
                file_get_contents(__DIR__ . '/prompts/additionalSystemInstructions.txt'),
                sprintf("<file_tree>%s</file_tree>", implode("\n", $fileList)),
                sprintf("<readme>%s</readme>", $readme),
            ])
        );

        $output->writeln(sprintf("Generating map from explanation (%s) and files list (%d)",strlen($explanation),count($fileList)));
        $map = $this->generateChatText(
            $chat,
            $model,
            implode("\n", [
                file_get_contents(__DIR__ . '/prompts/mapInstructions.txt'),
                file_get_contents(__DIR__ . '/prompts/additionalSystemInstructions.txt'),
                sprintf("<explanation>%s</explanation>", $explanation),
                sprintf("<file_tree>%s</file_tree>", implode("\n", $fileList)),
            ])
        );

        $output->writeln(sprintf("Generating mermaid from explanation (%s) and map (%d)",strlen($explanation),strlen($map)));
        $mermaid = $this->generateChatText(
            $chat,
            $model,
            implode("\n", [
                file_get_contents(__DIR__ . '/prompts/mermaidInstructions.txt'),
                sprintf("<explanation>%s</explanation>", $explanation),
                sprintf("<map>%s</map>", $map),
            ])
        );

        return $mermaid;
    }

    private function getReadme(string $directory): string
    {
        foreach (['Readme.md', 'Readme.txt'] as $file) {
            $filename = $directory . "/" . $file;
            if (file_exists($filename)) {
                return file_get_contents($filename);
            }
        }
        return '';
    }

    private function generateFileList(string $directory):array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        array_map(static fn($pattern) => $finder->notContains($pattern), self::excludedFilePatterns,);
        array_map(static fn($pattern) => $finder->notPath($pattern), self::excludedPathPatterns);

        return array_values(array_map(static fn(\SplFileInfo $file) => str_replace($directory, '', $file->getPathname()), iterator_to_array($finder)));
    }

    private function generateChatText(ChatInterface $chat, string $model, string $prompt)
    {
        return $this->cache->get(
            md5($model . $prompt),
            static fn(ItemInterface $item) => $chat->generateText($prompt),
        );
    }
}
