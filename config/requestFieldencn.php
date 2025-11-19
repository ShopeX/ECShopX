<?php

use \EspierBundle\Services\Config\ConfigRequestFieldsService as Service;

return [
    // User Registration Module
    Service::MODULE_TYPE_MEMBER_INFO => [
        // Fields that must be enabled and required
        "must_start_required" => env("REQUEST_FIELD_MEMBER_INFO_MUST_START_REQUIRED", "username,mobile"),
        // Default content to display. Fields in must_start_required must exist in default, otherwise the business logic flow will have issues
        "default" => [
            "mobile"         => [
                "name"         => "Mobile",
                "is_open"      => true,
                "element_type" => "mobile",
                "is_required"  => true,
                "prompt"       => "Please enter your mobile number"
            ],
            "username"       => [
                "name"         => "Nickname",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => true,
                "prompt"       => "Please enter your nickname"
            ],
//            "avatar" => [
//                "name" => env("REQUEST_FIELD_MEMBER_INFO_LABEL_AVATAR", "Avatar"),
//                "is_open" => true,
//                "element_type" => "input",
//                "is_required" => false
//            ],
            "sex"            => [
                "name"         => "Gender",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "Please select your gender",
                "items"        => [
                    0 => "Unknown",
                    1 => "Male",
                    2 => "Female",
                ]
            ],
            "birthday"       => [
                "name"         => "Birthday",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "Please enter your birthday",
            ],
            "address"        => [
                "name"         => "Home Address",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => false,
                "prompt"       => "Please enter your home address",
            ],
            "email"          => [
                "name"         => "Email",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => false,
                "prompt"       => "Please enter your email address",
            ],
            "industry"       => [
                "name"         => "Industry",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "Please select your industry",
                "items"        => [
                    0  => "Finance/Banking/Investment",
                    1  => "Computer/Internet",
                    2  => "Media/Publishing/Film/Culture",
                    3  => "Government/Public Service",
                    4  => "Real Estate/Building Materials/Engineering",
                    5  => "Consulting/Legal",
                    6  => "Manufacturing",
                    7  => "Education/Training",
                    8  => "Healthcare",
                    9  => "Transportation/Logistics",
                    10 => "Retail/Trade",
                    11 => "Tourism/Vacation",
                    12 => "Other",
                ],
            ],
            "income"         => [
                "name"         => "Annual Income",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "Please select your annual income range",
                "items"        => [
                    0 => "Below 50,000",
                    1 => "50,000 ~ 150,000",
                    2 => "150,000 ~ 300,000",
                    3 => "Above 300,000",
                    4 => "Other",
                ],
            ],
            "edu_background" => [
                "name"         => "Education",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "Please select your education level",
                "items"        => [
                    0 => "Master or above",
                    1 => "Bachelor",
                    2 => "College",
                    3 => "High School/Technical School or below",
                    4 => "Other",
                ],
            ],
            "habbit"         => [
                "name"         => "Hobbies",
                "is_open"      => true,
                "element_type" => "checkbox",
                "is_required"  => false,
                "prompt"       => "Please select your hobbies",
                "items"        => [
                    0  => [
                        "name"      => "Gaming",
                        "ischecked" => true,
                    ],
                    1  => [
                        "name"      => "Reading",
                        "ischecked" => true,
                    ],
                    2  => [
                        "name"      => "Music",
                        "ischecked" => true,
                    ],
                    3  => [
                        "name"      => "Sports",
                        "ischecked" => true,
                    ],
                    4  => [
                        "name"      => "Anime",
                        "ischecked" => true,
                    ],
                    5  => [
                        "name"      => "Travel",
                        "ischecked" => true,
                    ],
                    6  => [
                        "name"      => "Home Decor",
                        "ischecked" => true,
                    ],
                    7  => [
                        "name"      => "Arts",
                        "ischecked" => true,
                    ],
                    8  => [
                        "name"      => "Pets",
                        "ischecked" => true,
                    ],
                    9  => [
                        "name"      => "Food",
                        "ischecked" => true,
                    ],
                    10 => [
                        "name"      => "Entertainment",
                        "ischecked" => true,
                    ],
                    11 => [
                        "name"      => "Movies/TV",
                        "ischecked" => true,
                    ],
                    12 => [
                        "name"      => "Health & Wellness",
                        "ischecked" => true,
                    ],
                    13 => [
                        "name"      => "Digital Gadgets",
                        "ischecked" => true
                    ],
                    14 => [
                        "name"      => "Other",
                        "ischecked" => true,
                    ]
                ]
            ],
        ],
    ],
    // Community Group Leader Module
    Service::MODULE_TYPE_CHIEF_INFO => [
        // Fields that must be enabled and required
        "must_start_required" => env("REQUEST_FIELD_CHIEF_INFO_MUST_START_REQUIRED", "chief_name"),
        // Default content to display. Fields in must_start_required must exist in default, otherwise the business logic flow will have issues
        "default" => [
            "chief_name"       => [
                "name"         => "Name",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => true,
                "prompt"       => "Please enter your name",
            ],
        ],
    ],
];
