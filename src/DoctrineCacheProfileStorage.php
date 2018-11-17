<?php

namespace DracoBlue\DoctrineCacheProfileStorage;

use \Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

class DoctrineCacheProfileStorage implements ProfilerStorageInterface
{
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var string $prefix
     */
    private $prefix = 'profile-storage-';

    /**
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Finds profiler tokens for the given criteria.
     *
     * @param string   $ip     The IP
     * @param string   $url    The URL
     * @param string   $limit  The maximum number of tokens to return
     * @param string   $method The request method
     * @param int|null $start  The start date to search from
     * @param int|null $end    The end date to search to
     *
     * @return array An array of tokens
     */
    public function find($ip, $url, $limit, $method, $start = null, $end = null)
    {
        /* FIXME: this is not possible with doctrine cache */
    }

    /**
     * Reads data associated with the given token.
     *
     * The method returns false if the token does not exist in the storage.
     *
     * @param string $token A token
     *
     * @return Profile The profile associated with token
     */
    public function read($token)
    {
        $data = $this->cacheProvider->fetch($this->prefix . $token);

        if ($data === false) {
            return null;
        }

        return $this->createProfileFromData($token, unserialize($data));
    }

    /**
     * Saves a Profile.
     *
     * @param Profile $profile A Profile instance
     *
     * @return bool Write operation successful
     */
    public function write(Profile $profile)
    {
        $profileToken = $profile->getToken();
        // when there are errors in sub-requests, the parent and/or children tokens
        // may equal the profile token, resulting in infinite loops
        $parentToken = $profile->getParentToken() !== $profileToken ? $profile->getParentToken() : null;
        $childrenToken = array_filter(array_map(function ($p) use ($profileToken) {
            return $profileToken !== $p->getToken() ? $p->getToken() : null;
        }, $profile->getChildren()));

        // Store profile
        $data = array(
            'token' => $profileToken,
            'parent' => $parentToken,
            'children' => $childrenToken,
            'data' => $profile->getCollectors(),
            'ip' => $profile->getIp(),
            'method' => $profile->getMethod(),
            'url' => $profile->getUrl(),
            'time' => $profile->getTime(),
            'status_code' => $profile->getStatusCode(),
        );

        $this->cacheProvider->save($this->prefix . $profileToken, serialize($data));

        return true;
    }

    /**
     * Purges all data from the database.
     */
    public function purge()
    {
    }


    protected function createProfileFromData($token, $data, $parent = null)
    {
        $profile = new Profile($token);
        $profile->setIp($data['ip']);
        $profile->setMethod($data['method']);
        $profile->setUrl($data['url']);
        $profile->setTime($data['time']);
        $profile->setStatusCode($data['status_code']);
        $profile->setCollectors($data['data']);

        if (!$parent && $data['parent']) {
            $parent = $this->read($data['parent']);
        }

        if ($parent) {
            $profile->setParent($parent);
        }

        foreach ($data['children'] as $childToken) {
            if (!$childToken) {
                continue;
            }

            $data = $this->cacheProvider->fetch($this->prefix . $childToken);

            if ($data !== false) {
                $profile->addChild($this->createProfileFromData($childToken, unserialize($data), $profile));
            }
        }

        return $profile;
    }
}
