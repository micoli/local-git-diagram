## Basic Symfony CLI port of https://github.com/ahmedkhaleel2004/gitdiagram on symfony 

# Installation

```bash
make docker-build
```

# Graph generation

## Using default `mistral:latest`
```bash
docker run --network=host -v /tmp:/cache -v "/home/jdoe/src/project1":/src local-build/ai-src-diagram
```

## Using customModel

```bash
docker run --network=host -v /tmp:/cache -v "/home/jdoe/src/project1":/src local-build/ai-src-diagram --model llama3:8b
```

## Using custom ollama url

```bash
docker run --network=host -v /tmp:/cache -v "/home/jdoe/src/project1":/src local-build/ai-src-diagram --ollamaUrl ollamaUrl
```
