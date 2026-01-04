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

namespace EspierBundle\Commands;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use LaravelDoctrine\ORM\Console\Command;

class GenerateRepositoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'doctrine:generate:repositories
{--em= : Generate getter and setter for a specific entity manager. },
{--table= : 执行指定的Entities文件中定义的表名，不包含php.}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generates Repositories based on mapping files';

    private $openRepositoriesFile;

    /**
     * Fire the command
     * @param  ManagerRegistry                            $registry
     * @throws \Doctrine\ORM\Tools\Export\ExportException
     */
    public function handle(ManagerRegistry $registry)
    {
        $names = $this->option('em') ? [$this->option('em')] : $registry->getManagerNames();

        foreach ($names as $name) {
            $em = $registry->getManager($name);

            $this->comment('');
            $this->message('Generating getter and setter for <info>' . $name . '</info> entity manager...', 'blue');

            $cmf = new DisconnectedClassMetadataFactory();
            $cmf->setEntityManager($em);
            $metadatas = $cmf->getAllMetadata();

            $metadatas = MetadataFilter::filter($metadatas, 'Bundle');
            foreach ($metadatas as $metadata) {
                $tableName = $metadata->table['name'];
                if ($tableName != $this->option('table')) {
                    continue;
                }

                $customRepositoryClassName = $metadata->customRepositoryClassName;
                $entitiesName = $metadata->name;
                $identifier = $metadata->identifier;
                $fieldMappings = $metadata->fieldMappings;
                $fieldNames = $metadata->fieldNames;
                $columnNames = $metadata->columnNames;

                if (class_exists($customRepositoryClassName)) {
                    throw new \InvalidArgumentException(
                        sprintf(PHP_EOL.'%s.php 文件已存在，不需要重复生成！"', $customRepositoryClassName)
                    );
                }


                $this->FunOpenRepositoriesFile($customRepositoryClassName);

                $this->fileHeader($entitiesName, $customRepositoryClassName);
                $this->setTableName($tableName);
                $this->setTableCols($columnNames);
                $this->writeFun($entitiesName, $fieldMappings, $identifier);

                fwrite($this->openRepositoriesFile, "}\n");
            }
        }
    }

    private function FunOpenRepositoriesFile($customRepositoryClassName)
    {
        $filePath = base_path('src').'/'. str_replace('\\', '/', $customRepositoryClassName).'.php';

        $pathParts = pathinfo($filePath);

        if (!is_dir($pathParts['dirname'])) {
            mkdir($pathParts['dirname'], 0777, true);
        }

        $this->openRepositoriesFile = fopen($filePath, "w+");

        $this->message('创建文件 ' . $filePath . ' 成功', 'blue');

        return true;
    }

    /**
     * 生成文件头部定义
     */
    private function fileHeader($entitiesName, $customRepositoryClassName)
    {
        $namespaceArr = explode('/', str_replace('\\', '/', $customRepositoryClassName));
        $namespace = $namespaceArr[0].'\\'.$namespaceArr[1];

        $head = "<?php
namespace {$namespace};

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use {$entitiesName};

use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class {$namespaceArr[2]} extends EntityRepository
{\n";
        fwrite($this->openRepositoriesFile, $head);

        return true;
    }

    /**
     * 写入类的方法
     */
    private function writeFun($entitiesName, $fieldMappings, $identifier)
    {
        $this->setCreateFun($entitiesName);
        $this->setUpdateFun();
        $this->setDeleteFun();
        $this->setGetFun($identifier);
    }
    private function setTableName($tableName)
    {
        fwrite($this->openRepositoriesFile, '
    public $table = "'.$tableName.'";');
    }

    private function setTableCols($cols)
    {
        $colsArr = "['". implode("','", $cols)."']";

        $str = ' ';
        fwrite($this->openRepositoriesFile, '
    public $cols = '.$colsArr.';');
    }


    private function setCreateFun($entitiesName)
    {
        $entitiesNameArr = explode('/', str_replace('\\', '/', $entitiesName));

        fwrite($this->openRepositoriesFile, '
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)');
        fwrite($this->openRepositoriesFile, "\n    {");

        $entityStr = '    $entity = new '.$entitiesNameArr[2].'();';
        fwrite($this->openRepositoriesFile, "\n    $entityStr");

        $funStr = '    $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);';
        fwrite($this->openRepositoriesFile, "\n    $funStr");
        fwrite($this->openRepositoriesFile, "\n    }\n");
    }

    private function setGetFun($identifier)
    {
        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach($filter as $field => $value) {
            $list = explode(\'|\', $field);
            if (count($list) > 1) {
                list($v,$k) = $list;
                if ($k == \'contains\') {
                    $k = \'like\';
                }
                if ($k == \'like\') {
                    $value = \'%\'.$value.\'%\';
                }
                if (is_array($value)) {
                    array_walk($value, function(&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb =$qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function(&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $cols=\'*\', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app(\'registry\')->getConnection(\'default\');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page-1)*$pageSize)
              ->setMaxResults($pageSize);
        }
        $lists = $qb->execute()->fetchAll();
        return $lists;
     }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 根据条件获取列表数据,包含数据总数条数
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols=\'*\', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result[\'total_count\'] = $this->count($filter);
        if ($result[\'total_count\'] > 0) {
            $conn = app(\'registry\')->getConnection(\'default\');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page-1)*$pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result[\'list\'] = $lists ?? [];
        return $result;
     }'."\n"
        );
        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if( !$entity ) {
            return [];
        }

        return $this->getColumnNamesData($entity);
     }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if( !$entity ) {
            return [];
        }

        return $this->getColumnNamesData($entity);
     }'."\n"
        );

        if (($identifier ?? []) && is_array($identifier) && count($identifier) == 1) {
            $cols = reset($identifier);
        } else {
            $cols = '*';
        }

        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 统计数量
     */
     public function count($filter)
     {
         $conn = app(\'registry\')->getConnection(\'default\');
         $qb = $conn->createQueryBuilder();
         $qb->select(\'count('.$cols.')\')
             ->from($this->table);
         if ($filter) {
             $this->_filter($filter, $qb);
         }
         $count = $qb->execute()->fetchColumn();
         return intval($count);
      }'."\n"
        );
    }

    private function setUpdateFun()
    {
        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if( !$entity ) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
     }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach($data as $key=>$val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }'."\n"
        );
    }

    private function setDeleteFun()
    {
        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if(!$entity) {
            return true;
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $entityList = $this->findBy($filter);
        if(!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    private function setColumnNamesData($entity, $params)
    {
        foreach($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (method_exists($entity, $fun)) {
                     $entity->$fun($params[$col]);
                }
            }
        }
        return $entity;
    }'."\n"
        );

        fwrite(
            $this->openRepositoriesFile,
            '
    private function getColumnNamesData($entity, $cols=[], $ignore=[])
    {
        if (!$cols) $cols = $this->cols;

        $values = [];
        foreach($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            if (method_exists($entity, $fun)) {
                $values[$col] = $entity->$fun();
            }
        }
        return $values;
    }'."\n"
        );
    }
}
