<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands;

use LaravelDoctrine\Migrations\Configuration\ConfigurationProvider;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Console\Command;

class GenerateDataDictionaryCommand extends Command
{
    // KEY: U2hvcEV4
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctrine:generate:dataDictionary
{--connection: },
';

    /**
     * The console command description.
     * @var string
     */
    protected $description = '生成数据辞典';

    protected function getSchemaProvider(EntityManagerInterface $em)
    {
        return new OrmSchemaProvider($em);
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(
        ConfigurationProvider $provider,
        ManagerRegistry $registry
    ) {
        @ini_set('memory_limit', -1);
        $configuration = $provider->getForConnection();
        $em = $registry->getManager();
        $platform = $registry->getConnection()->getDatabasePlatform();

        $toSchema = $this->getSchemaProvider($em)->createSchema();
        $realTables = [];
        foreach ($toSchema->getTables() as $table) {
            $realColumns = [];
            foreach ($table->getColumns() as $column) {
                $realColumn = $column->toArray();
                $realColumn['name'] = str_replace('_', '\_', $realColumn['name']);
                $realColumn['comment'] = str_replace('_', '\_', $realColumn['comment']);
                $realColumn['type'] = $column->getType()->getSQLDeclaration($column->toArray(), $platform);
                $realColumn['default'] = $realColumn['default'] ?? 'NULL';
                $realColumn['notnull'] = ($realColumn['notnull'] == true) ? 'Yes' : 'No';
                $realColumn['autoincrement'] = ($realColumn['autoincrement'] == true) ? 'Yes' : 'No';
                $realColumns[] = $realColumn;
            }

            $realIndexes = [];
            foreach ($table->getIndexes() as $indexName => $index) {
                $realIndex = [];
                $realIndex['name'] = str_replace('_', '\_', $indexName);
                $indexColumns = $index->getColumns();
                $newIndexColumns = [];
                foreach ($indexColumns as $indexColumn) {
                    $indexColumn = str_replace('_', '\_', $indexColumn);
                    $newIndexColumns[] = $indexColumn;
                }
                //echo '-----'.PHP_EOL;
                //var_dump($indexColumns);
                $realIndex['columns'] = implode(', ', $newIndexColumns);
                $realIndex['isUnique'] = $index->isUnique() ? 'Yes' : 'No';
                $realIndexes[] = $realIndex;
            }
            $realTable['orig_name'] = $table->getName();
            $realTable['name'] = str_replace('_', '\_', $table->getName());
            $realTable['comment'] = str_replace('_', '\_', $table->getComment());
            $realTable['columns'] = $realColumns;
            $realTable['indexes'] = $realIndexes;

            $realTables[] = $realTable;


            //if (!preg_match($filterExpr, $this->resolveTableName($tableName))) {
            //  $toSchema->dropTable($tableName);
            //}
        }
        $www = view('dataDictionary', ['tables' => $realTables]);
        echo $www;
    }
}
