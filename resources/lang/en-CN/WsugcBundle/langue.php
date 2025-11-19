<?php 

$api = [

];

$service = [
    'PostService_getPostStatusText' => [
        '0'=> 'Comming Soon',
        '1'=> 'In Progress',
        '2'=> 'Closed',
        '3'=> 'Fully Booked',
        '4'=> 'Fully Booked'
    ],
    'CommentService_getStatusText' => [
        '0'=> 'Pending',            
        '1'=> 'Approved',
        '2'=> 'Rejected',
        '3'=> 'Fully Booked',
        '4'=> 'Fully Booked'
    ],
    'TopicService_getTagStatusText' => [
        '0'=> 'Pending',            
        '1'=> 'Approved',
        '2'=> 'Rejected',
        '3'=> 'Fully Booked',
        '4'=> 'Fully Booked'
    ]
];

$repository = [
  
];

return array_merge($api,$service,$repository);
