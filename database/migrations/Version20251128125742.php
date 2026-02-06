<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20251128125742 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
        CREATE TABLE `kujiale_designer_goods` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `good_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '渲染图ID',
  `dimensions` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '尺寸',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '描述',
  `brand_good_code` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品编码',
  `brand_good_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品名称',
  `brand_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '品牌id',
  `brand_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '品牌名称',
  `series_tag_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列id',
  `series_tag_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列名称',
  `product_number` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '型号',
  `customer_texture` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '材质',
  `buy_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '购买链接',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_good_id` (`good_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $this->addSql("
            CREATE TABLE `kujiale_designer_goods_rel` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pic_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '渲染图ID',
  `obs_brand_good_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '商品ID',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_brand_good_id` (`obs_brand_good_id`),
  KEY `idx_pic_id` (`pic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $this->addSql("
        CREATE TABLE `kujiale_designer_tags` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `tag_category_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签类目id',
  `tag_category_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签类目名',
  `type` int(11) DEFAULT NULL COMMENT '类型',
  `is_multiple_selected` int(11) DEFAULT NULL COMMENT '是否支持多选',
  `is_disabled` int(11) DEFAULT NULL COMMENT '是否禁用',
  `tag_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签id',
  `tag_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签名称',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`tag_category_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->addSql("
        CREATE TABLE `kujiale_designer_works` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `design_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '方案名称',
  `cover_pic` text COLLATE utf8mb4_unicode_ci COMMENT '方案封面',
  `is_origin` bigint(20) DEFAULT NULL COMMENT '是否原创',
  `is_excellent` bigint(20) DEFAULT NULL COMMENT '是否优秀',
  `is_real_excellent` bigint(20) DEFAULT NULL COMMENT '是否优秀',
  `is_top` bigint(20) DEFAULT NULL COMMENT '是否置顶',
  `design_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '方案ID',
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型ID',
  `comm_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '小区',
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '城市',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型名称',
  `tag_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '方案分类id',
  `design_pano_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '全景漫游url',
  `user_avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户头像',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户名',
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户id',
  `organization_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '组织id',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  `view_count` int(11) DEFAULT NULL COMMENT '浏览量',
  `like_count` int(11) DEFAULT NULL COMMENT '点赞数',
  `ku_created` int(11) DEFAULT NULL COMMENT '方案更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_design_id` (`design_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_plan_id` (`plan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $this->addSql("
        CREATE TABLE `kujiale_designer_works_level` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `design_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '方案id',
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型ID',
  `spec_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型的房型',
  `src_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型的建筑面积',
  `area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型的套内建筑面积',
  `real_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型的套内面积',
  `plan_pic` text COLLATE utf8mb4_unicode_ci COMMENT '户型图的URL',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_design_id` (`design_id`),
  KEY `idx_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->addSql("
        CREATE TABLE `kujiale_designer_works_like` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `design_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '方案id',
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型ID',
  `user_id` int(11) DEFAULT NULL COMMENT '点赞用户id',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_design_id` (`design_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $this->addSql("
            CREATE TABLE `kujiale_designer_works_pic` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pic_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '渲染图ID',
  `pic_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '渲染图类型。0表示普通渲染图，1表示全景图，3表示俯视图',
  `pic_detail_type` bigint(20) DEFAULT NULL COMMENT '渲染图类型细分',
  `room_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '渲染图所属房间的名字',
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '渲染图URL',
  `pano_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '全景图的链接地址',
  `design_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '方案ID',
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型ID',
  `level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '渲染图所在房间的楼层信息，正为地上，负为地下室，不存在0层',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_design_id` (`design_id`),
  KEY `idx_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->addSql("
            CREATE TABLE `kujiale_designer_works_rel_tags` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `tag_category_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签类目id',
  `tag_category_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签类目名',
  `tag_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签id',
  `tag_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签名称',
  `design_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '方案id',
  `plan_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '户型ID',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_design_id` (`design_id`),
  KEY `idx_category_id` (`tag_category_id`),
  KEY `idx_plan_id` (`plan_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
