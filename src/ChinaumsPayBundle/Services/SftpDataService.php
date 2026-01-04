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

namespace ChinaumsPayBundle\Services;

/**
 * sftp上传、下载
 */
class SftpDataService
{
    // 初始配置为NULL
    private $config = NULL;
    // 连接为NULL
    private $conn = NULL;
    //sftp resource 
    private $ressftp = NULL;
    // 初始化
    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    public function connect()
    {
        $this->conn = ssh2_connect($this->config['host'], $this->config['port']);
        if( ssh2_auth_password($this->conn, $this->config['username'], $this->config['password'])){
            $this->ressftp = ssh2_sftp($this->conn);
        }else{
            echo "用户名或密码错误";
        }
    }

    // 下载文件
    public function downftp($remote, $local)
    {
        return copy("ssh2.sftp://{$this->ressftp}".$remote, $local);
    }

    // 文件上传
    public function upftp( $local,$remote, $file_mode = 0777)
    {
        return copy($local,"ssh2.sftp://{$this->ressftp}".$remote);
    }

    //创建目录
    public function ssh2_sftp_mchkdir($path)  //使用创建目录循环
    {
        ssh2_sftp_mkdir($this->ressftp, $path,0777,true);
    }
    
    //判段目录是否存在
    public function ssh2_dir_exits($dir)
    {
        return file_exists("ssh2.sftp://{$this->ressftp}".$dir);
    }
}
