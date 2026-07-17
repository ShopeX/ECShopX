<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260717202820 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $conn = app('registry')->getConnection('default');
        $conn->createQueryBuilder()->delete('espier_address')->execute();

        ignore_user_abort(true);
        set_time_limit(3600);

        $a = file_get_contents(storage_path('static/district.json'));
        $a = json_decode($a, true);
        $conn = app('registry')->getConnection('default');

        foreach ($a as $v) {
            $row = [
                'id' => $v['value'],
                'label' => $v['label'],
                'parent_id' => '0',
                'path' => $v['value']
            ];
            $conn->insert('espier_address', $row);
            foreach ($v['children'] as $v1) {
                $row = [
                    'id' => $v1['value'],
                    'label' => $v1['label'],
                    'parent_id' => $v['value'],
                    'path' => implode(',', [$v['value'], $v1['value']])
                ];
                $conn->insert('espier_address', $row);
                foreach ($v1['children'] as $v2) {
                    $row = [
                        'id' => $v2['value'],
                        'label' => $v2['label'],
                        'parent_id' => $v1['value'],
                        'path' => implode(',', [$v['value'], $v1['value'], $v2['value']])
                    ];
                    $conn->insert('espier_address', $row);
                }

            }
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
