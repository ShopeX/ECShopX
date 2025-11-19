<?php 

$api = [

];

$service = [
    'PostService_getPostStatusText' => [
        '0'=> '待审核',
        '1'=> '审核通过',
        '2'=> '机器拒绝',
        '3'=> '待人工审核',
        '4'=> '已拒绝'
    ],
    'CommentService_getStatusText' => [
        '0'=> '待审核',
        '1'=> '审核通过',
        '2'=> '机器拒绝',
        '3'=> '待人工审核',
        '4'=> '已拒绝'
    ],
    'TopicService_getTagStatusText' => [
        '0'=> '待审核',
        '1'=> '审核通过',
        '2'=> '机器拒绝',
        '3'=> '待人工审核',
        '4'=> '已拒绝'
    ]
];

$repository = [
  
];

return array_merge($api,$service,$repository);
