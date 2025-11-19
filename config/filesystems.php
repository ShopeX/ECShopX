<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "s3", "qiniu"
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 'qiniu'),

    'upload_file_handle' => [
        'member_info'                   => \MembersBundle\Services\MemberUploadService::class,
        'member_update'                 => \MembersBundle\Services\MemberUploadUpdateService::class,
        'member_consume'                => \MembersBundle\Services\MemberUploadConsumService::class,
        'supplier_goods'                  => \SupplierBundle\Services\SupplierGoodsUploadService::class,
        'normal_goods'                  => \GoodsBundle\Services\NormalGoodsUploadService::class,
        'normal_epidemic_goods'         => \GoodsBundle\Services\EpidemicItemsService::class,
        'normal_goods_store'            => \GoodsBundle\Services\NormalGoodsStoreUploadService::class,
        'normal_goods_profit'           => \GoodsBundle\Services\NormalGoodsProfitUploadService::class,
        'normal_goods_tag'              => \GoodsBundle\Services\NormalGoodsTagUploadService::class,
        'selform_registration_record'   => \SelfserviceBundle\Services\RegistrationRecordReviewService::class,
        'normal_orders'                 => \OrdersBundle\Services\Orders\NormalOrdersUploadService::class,
        'normal_orders_cancel'          => \OrdersBundle\Services\Orders\NormalOrdersCancelUploadService::class,
        'normal_pointsmall_goods'       => \PointsmallBundle\Services\NormalGoodsUploadService::class,
        'normal_pointsmall_goods_store' => \PointsmallBundle\Services\NormalGoodsStoreUploadService::class,
        'whitelist_create'              => \MembersBundle\Services\MemberWhitelistUploadService::class,
        'marketing_goods'               => \GoodsBundle\Services\MarketingGoodsUploadService::class,
        'purchase_goods'                => \GoodsBundle\Services\PurchaseGoodsUploadService::class,
        'discount_goods'                => \GoodsBundle\Services\DiscountGoodsUploadService::class,
        'update_distribution_item'      => \EspierBundle\Services\File\UpdateDistributionItemTemplate::class,
        'limit_sale_item'               => \PromotionsBundle\Services\LimitSaleItemUploadService::class,
        'adapay_tradedata'              => \AdaPayBundle\Services\AdapayTradeDataUploadService::class,
        'community_chief'               => \CommunityBundle\Services\CommunityChiefUploadService::class,
        'employee_purchase_employees'   => \EmployeePurchaseBundle\Services\EmployeesUploadService::class,
        'employee_purchase_activity_items'   => \EmployeePurchaseBundle\Services\ActivityItemsUploadService::class,
        'upload_distributor_white'      => \DistributionBundle\Services\UploadWhiteListService::class,
        'distributor_info'              => \DistributionBundle\Services\UploadDistributor::class,
        'upload_tb_items'               => \GoodsBundle\Services\Upload\UploadTbItems::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */
    'disks' => config_ext('filesystems')->get(env('DISK_DRIVER','qiniu')),

    // 当前OSS项目名
    // 与原先不同，所有客户项目将使用相同桶
    'current_project_name' => env('OSS_PROJECT_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
