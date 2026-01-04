<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace EspierBundle\Services;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;

use Qiniu\Auth;
use Qiniu\Cdn\CdnManager;

class AwsAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    protected $s3;

    /**
     * @var string
     */
    protected $accessKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var string
     */
    protected $region;

    /**
     * @var string
     */
    protected $endpoint;


    /**
     * AwsAdapter constructor.
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     */
    public function __construct($accessKey, $secretKey, $bucket, $region, $endpoint)
    {
        $this->s3 = app('aws')->createClient('s3');
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->endpoint = $endpoint;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $result = $this->s3->putObject(array(
            'Bucket' => $this->bucket,
            'Key' => $path,
//            'SourceFile' => $file_path,
            'Body' => $contents,
        ));
        $res['url'] = $result['ObjectURL'];
        return $res;
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false|void false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false|void false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false|void false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newPath
     *
     * @return bool|void
     */
    public function rename($path, $newPath)
    {
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newPath
     *
     * @return bool|void
     */
    public function copy($path, $newPath)
    {
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool|void
     */
    public function delete($path)
    {
    }

    /**
     * Delete a directory.
     *
     * @param string $directory
     *
     * @return bool|void
     */
    public function deleteDir($directory)
    {
    }

    /**
     * Create a directory.
     *
     * @param string $directory directory name
     * @param Config $config
     *
     * @return array|false|void
     */
    public function createDir($directory, Config $config)
    {
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null|void
     */
    public function has($path)
    {
    }


    /**
     * Get resource url.
     *
     * @param string $path
     *
     * @return string
     */
    public function getUrl($path)
    {
        // 判断是否有自定义域名
        if (!empty($this->endpoint)) {
            $path_info = pathinfo($path);
            if (substr($path_info['dirname'], 0, 4) == 'http') {
                $url = $this->endpoint . parse_url($path)['path'];
            } else {
                $url = $this->endpoint . '/' . $path;
            }
        } else {
            $url = $path;
        }
        return $url;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false|void
     */
    public function read($path)
    {
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false|void
     */
    public function readStream($path)
    {
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array|void
     */
    public function listContents($directory = '', $recursive = false)
    {
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false|void
     */
    public function getMetadata($path)
    {
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false|void
     */
    public function getSize($path)
    {
    }

    /**
     * Fetch url to bucket.
     *
     * @param string $path
     * @param string $url
     *
     * @return array|false|void
     */
    public function fetch($path, $url)
    {
    }

    /**
     * Get private file download url.
     *
     * @param string $path
     * @param int $expires
     *
     * @return string
     */
    public function privateDownloadUrl($path, $expires = 3600)
    {
        return $this->s3->getObjectUrl($this->bucket, $path, $expires);
    }

    /**
     * Refresh file cache.
     *
     * @param string|array $path
     *
     * @return array|void
     */
    public function refresh($path)
    {
    }

    /**
     * Get the mime-type of a file.
     *
     * @param string $path
     *
     * @return array|false|void
     */
    public function getMimeType($path)
    {
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false|void
     */
    public function getTimestamp($path)
    {
    }


    /**
     * @param \Qiniu\Auth $manager
     *
     * @return $this|void
     */
    public function setAuthManager(Auth $manager)
    {
    }

    /**
     * @param CdnManager $manager
     *
     * @return $this|void
     */
    public function setCdnManager(CdnManager $manager)
    {
    }


    /**
     * @return string|void
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get the upload token.
     *
     * @param string|null $key
     * @param int $expires
     * @param string|null $policy
     * @param string|null $strictPolice
     *
     * @return string|void
     */
    public function getUploadToken($key = null, $expires = 3600, $policy = null, $strictPolice = null)
    {
    }

    /**
     * @param array $stats
     *
     * @return array|void
     */
    protected function normalizeFileInfo(array $stats)
    {
    }

    /**
     * @param string $domain
     *
     * @return string|void
     */
    protected function normalizeHost($domain)
    {
    }

    /**
     * Does a UTF-8 safe version of PHP parse_url function.
     *
     * @param string $url URL to parse
     *
     * @return mixed associative array or false if badly formed URL
     *
     * @see     http://us3.php.net/manual/en/function.parse-url.php
     * @since   11.1
     */
    protected static function parseUrl($url)
    {
    }
}
