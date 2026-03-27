<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Console;

use DistributionBundle\Services\DistributorService;
use Illuminate\Console\Command;
use ThirdPartyBundle\Services\Map\MapService;

/**
 * 为经纬度为空的店铺根据地址补全经纬度
 * 逻辑与文档《创建店铺-地址转经纬度逻辑梳理》一致：使用 MapService 地址解析
 */
class FillDistributorLatLngCommand extends Command
{
    protected $signature = 'distributor:fill-lat-lng
                            {--company_id= : 仅处理指定公司ID，不传则处理所有公司}
                            {--limit=0 : 每批最多处理条数，0表示不限制}
                            {--dry-run : 只列出会处理的店铺，不实际更新}';

    protected $description = '查找经纬度为空的店铺，根据地址解析并回写经纬度';

    public function handle()
    {
        $companyId = $this->option('company_id');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $conn = app('registry')->getConnection('default');
        $table = 'distribution_distributor';

        $qb = $conn->createQueryBuilder()
            ->select('distributor_id', 'company_id', 'name', 'province', 'city', 'area', 'address', 'house_number', 'lat', 'lng')
            ->from($table)
            ->where('(lat IS NULL OR lat = \'\') OR (lng IS NULL OR lng = \'\')')
            ->andWhere('(is_valid IS NULL OR is_valid != \'delete\')');

        if ($companyId !== null && $companyId !== '') {
            $qb->andWhere($qb->expr()->eq('company_id', $qb->expr()->literal($companyId)));
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        $rows = $qb->execute()->fetchAllAssociative();
        $total = count($rows);

        if ($total === 0) {
            $this->info('没有需要补全经纬度的店铺。');
            return 0;
        }

        $this->info("共找到 {$total} 个经纬度为空的店铺。" . ($dryRun ? ' [dry-run，不更新]' : ''));

        $distributorService = new DistributorService();
        $updated = 0;
        $failed = 0;
        $noAddress = 0;

        foreach ($rows as $row) {
            $distributorId = (int) $row['distributor_id'];
            $companyIdNum = (int) $row['company_id'];
            $name = $row['name'] ?? '';
            $province = trim((string) ($row['province'] ?? ''));
            $city = trim((string) ($row['city'] ?? ''));
            $area = trim((string) ($row['area'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));
            $houseNumber = trim((string) ($row['house_number'] ?? ''));

            $addressDetail = $address;
            if ($houseNumber !== '') {
                $addressDetail = $addressDetail === '' ? $houseNumber : $addressDetail . ' ' . $houseNumber;
            }
            $region = $city;
            $fullAddress = trim($province . $city . $area . $addressDetail);
            if ($fullAddress === '') {
                $this->warn("  [{$distributorId}] {$name} 无有效地址，跳过");
                $noAddress++;
                continue;
            }

            try {
                $mapData = MapService::make($companyIdNum)->getLatAndLng(
                    $region,
                    $fullAddress
                );
                $lat = $mapData && $mapData->getLat() !== '' ? $mapData->getLat() : null;
                $lng = $mapData && $mapData->getLng() !== '' ? $mapData->getLng() : null;

                if ($lat === null || $lng === null || $lat === '' || $lng === '') {
                    $this->warn("  [{$distributorId}] {$name} 地址解析无经纬度: {$fullAddress}");
                    $failed++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [{$distributorId}] {$name} => lat={$lat}, lng={$lng}");
                    $updated++;
                    continue;
                }

                $distributorService->updateDistributor($distributorId, [
                    'company_id' => $companyIdNum,
                    'lat' => (string) $lat,
                    'lng' => (string) $lng,
                ]);
                $this->line("  [{$distributorId}] {$name} 已更新 lat={$lat}, lng={$lng}");
                $updated++;
            } catch (\Throwable $e) {
                $this->warn("  [{$distributorId}] {$name} 解析失败: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info('');
        $this->info('--- 结果 ---');
        $this->info("处理: {$total}，成功: {$updated}，无地址跳过: {$noAddress}，失败: {$failed}");
        return 0;
    }
}
