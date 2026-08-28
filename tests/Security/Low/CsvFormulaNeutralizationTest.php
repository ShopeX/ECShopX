<?php

declare(strict_types=1);

namespace Tests\Security\Low;

use EspierBundle\Services\ExportFileService;
use Mockery;
use TestCase;

/**
 * codex-security-05-low T40-RED / F-173 / TC-05-02
 */
class CsvFormulaNeutralizationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-05-02 / F-173：CSV 导出须中和以 =,+,-,@ 开头的公式注入字段。
     * #given 含 =1+1 的导出数据
     * #when 调用 exportCsv
     * #then 输出须含中和前缀（单引号）而非裸公式
     */
    public function testTc0502CsvFormulaPrefixIsNeutralized(): void
    {
        #given
        $disk = Mockery::mock();
        $disk->shouldReceive('put')->once()->andReturn(true);
        $disk->shouldReceive('privateDownloadUrl')->andReturn('https://example.com/export.csv');
        $filesystem = Mockery::mock();
        $filesystem->shouldReceive('disk')->with('import-file')->andReturn($disk);
        $this->app->instance('filesystem', $filesystem);

        $fileName = 'tc0502-' . uniqid('', true);
        $csvPath = storage_path('csv/' . $fileName . '.csv');

        try {
            #when
            $service = new ExportFileService();
            $service->exportCsv($fileName, ['name' => 'Name'], [[['name' => '=1+1']]]);

            #then
            $this->assertFileExists($csvPath);
            $content = (string) file_get_contents($csvPath);
            $this->assertStringContainsString(
                "'=1+1",
                $content,
                'TC-05-02: spreadsheet formula prefix =1+1 must be neutralized in CSV export'
            );
        } finally {
            if (is_file($csvPath)) {
                unlink($csvPath);
            }
        }
    }
}
