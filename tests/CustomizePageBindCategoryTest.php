<?php

use GoodsBundle\Services\ItemsCategoryService;

class CustomizePageBindCategoryTest extends TestCase
{
    /** @var ItemsCategoryService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ItemsCategoryService();
    }

    public function testPlatformRequiresMainCategoryBinding(): void
    {
        $this->assertTrue($this->service->isCustomizePageBindMainCategory('platform'));
        $this->assertSame('只能绑定一级管理分类', $this->service->getCustomizePageBindCategoryErrorMessage('platform'));
    }

    public function testStandardRequiresSalesCategoryBinding(): void
    {
        $this->assertFalse($this->service->isCustomizePageBindMainCategory('standard'));
        $this->assertSame('只能绑定一级销售分类', $this->service->getCustomizePageBindCategoryErrorMessage('standard'));
    }

    public function testB2cRequiresSalesCategoryBinding(): void
    {
        $this->assertFalse($this->service->isCustomizePageBindMainCategory('b2c'));
        $this->assertSame('只能绑定一级销售分类', $this->service->getCustomizePageBindCategoryErrorMessage('b2c'));
    }

    public function testValidateAcceptsFirstLevelMainCategoryForPlatform(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 1,
            'is_main_category' => true,
        ], 'platform');

        $this->assertNull($error);
    }

    public function testValidateRejectsSalesCategoryForPlatform(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 1,
            'is_main_category' => false,
        ], 'platform');

        $this->assertSame('只能绑定一级管理分类', $error);
    }

    public function testValidateAcceptsFirstLevelSalesCategoryForB2c(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 1,
            'is_main_category' => false,
        ], 'b2c');

        $this->assertNull($error);
    }

    public function testValidateRejectsMainCategoryForB2c(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 1,
            'is_main_category' => true,
        ], 'b2c');

        $this->assertSame('只能绑定一级销售分类', $error);
    }

    public function testValidateAcceptsFirstLevelSalesCategoryForStandard(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 1,
            'is_main_category' => false,
        ], 'standard');

        $this->assertNull($error);
    }

    public function testValidateRejectsMainCategoryForStandard(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 1,
            'is_main_category' => true,
        ], 'standard');

        $this->assertSame('只能绑定一级销售分类', $error);
    }

    public function testValidateRejectsNonTopLevelCategory(): void
    {
        $error = $this->service->validateCustomizePageBindCategory([
            'category_level' => 2,
            'is_main_category' => true,
        ], 'platform');

        $this->assertSame('只能绑定一级管理分类', $error);
    }
}
