# doctrine-cache-profile-storage

By default the profiler for symfony stores it's data in file storage. If you want 
to use memcached or redis instead, you need a ProfileStorageInterface for this.

This library provides a simple `Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface`
for any `Doctrine\Common\Cache\CacheProvider`.

## Use Redis

Given:

1. REDIS_HOST and REDIS_PORT are set as environment variables. You might use parameters instead, too.
2. And redis extension is installed

```yaml
  app.profiler.redis:
      class: \Redis
      calls:
        - [ pconnect, [ '%env(REDIS_HOST)%', '%env(REDIS_PORT)%' ]]

  app.profiler.redis_cache:
      class: Doctrine\Common\Cache\RedisCache
      calls:
        - [ setNamespace, [ "profiler-" ]]
        - [ setRedis, [ "@app.profiler.redis" ]]

  profiler.storage:
      class: DracoBlue\DoctrineCacheProfileStorage\DoctrineCacheProfileStorage
      arguments:
          - "@app.profiler.redis_cache"
```

## Use Memcached

Given:

1. MEMCACHED_HOST and MEMCACHED_PORT are set as environment variables. You might use parameters instead, too.
2. And memcached extension is installed 


```yaml
  app.profiler.memcached:
      class: \Memcached
      arguments:
        - "profiler"
      calls:
        - [ addServer, [ '%env(MEMCACHED_HOST)%', '%env(MEMCACHED_PORT)%' ]]

  app.profiler.memcached_cache:
      class: Doctrine\Common\Cache\MemcachedCache
      calls:
        - [ setMemcached, [ "@app.profiler.memcached" ]]

  profiler.storage:
      class: DracoBlue\DoctrineCacheProfileStorage\DoctrineCacheProfileStorage
      arguments:
          - "@app.profiler.memcached_cache"

```

## License

This work is copyright by DracoBlue (<http://dracoblue.net>) and licensed under the terms of MIT License.