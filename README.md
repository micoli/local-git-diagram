## Basic Symfony CLI port of https://github.com/ahmedkhaleel2004/gitdiagram

```bash
make docker-build
docker run  --network=host -v /tmp:/cache -v "/home/jdoe/src/project1":/src local-build/ai-src-diagram
```
