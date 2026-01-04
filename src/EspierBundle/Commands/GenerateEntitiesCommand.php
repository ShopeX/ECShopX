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
use Doctrine\ORM\Tools\EntityGenerator;
use LaravelDoctrine\ORM\Console\Command;

class GenerateEntitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'doctrine:generate:entities
    {dest-path? : Path you want entities to be generated in }
    {--filter=* : A string pattern used to match entities that should be processed.}
    {--em= : Generate getter and setter for a specific entity manager. },
    {--extend= : Defines a base class to be extended by generated entity classes.}
    {--num-spaces=4 : Defines the number of indentation spaces.}
    {--no-backup : Flag to define if generator should avoid backuping existing entity file if it exists}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Generates entities based on mapping files';

    /**
     * Fire the command
     * @param  ManagerRegistry                            $registry
     * @throws \Doctrine\ORM\Tools\Export\ExportException
     */
    public function handle(ManagerRegistry $registry)
    {
        // Ref: 1996368445
        $names = $this->option('em') ? [$this->option('em')] : $registry->getManagerNames();

        foreach ($names as $name) {
            $em = $registry->getManager($name);

            $this->comment('');
            $this->message('Generating getter and setter for <info>' . $name . '</info> entity manager...', 'blue');

            $cmf = new DisconnectedClassMetadataFactory();
            $cmf->setEntityManager($em);
            $metadatas = $cmf->getAllMetadata();



            // 只处理Bundle 相关
            $metadatas = MetadataFilter::filter($metadatas, 'Bundle');
            $metadatas = MetadataFilter::filter($metadatas, $this->option('filter'));

            # $destPath = base_path($this->argument('dest-path') ?:'app/Entities');
            # 之前的这个默认值好坑
            $destPath = base_path($this->argument('dest-path') ?: 'src');
            if (!is_dir($destPath)) {
                mkdir($destPath, 0777, true);
            }

            $destPath = realpath($destPath);

            if (!file_exists($destPath)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Proxies destination directory ' < info>%s </info > ' does not exist.",
                        $em->getConfiguration()->getProxyDir()
                    )
                );
            }

            if (count($metadatas)) {

                // Create EntityGenerator
                $entityGenerator = new EntityGenerator();
                $entityGenerator->setGenerateAnnotations(false);
                $entityGenerator->setGenerateStubMethods(true);
                $entityGenerator->setRegenerateEntityIfExists(false);
                $entityGenerator->setUpdateEntityIfExists(true);
                $entityGenerator->setNumSpaces(4);
                $entityGenerator->setAnnotationPrefix('ORM\\');

                $entityGenerator->setBackupExisting(!$this->option('no-backup'));

                if (($extend = $this->option('extend')) !== null) {
                    $entityGenerator->setClassToExtend($extend);
                }

                foreach ($metadatas as $metadata) {
                    $this->comment(
                        sprintf('Processing entity "<info>%s</info>"', $metadata->name)
                    );
                    $entityGenerator->generate($metadatas, $destPath);
                }
                // Generating Entities


                // Outputting information message
                $this->comment(PHP_EOL . sprintf('Entity classes generated to "<info>%s</INFO>"', $destPath));
            }
        }
    }
}
