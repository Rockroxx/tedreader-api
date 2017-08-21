<?php namespace App\Ted;

class Parser
{

    const CONTACT_MULTI = [
        "official_name" => [[
            "OFFICIALNAME"
        ], ["ORGANISATION", "OFFICIALNAME"]],
        "country" => [[
            "COUNTRY" => ["attribute" => "VALUE"]
        ]],
        "city" => [[
            "TOWN"
        ]],
        "street" => [[
            "ADDRESS"
        ]],
        "postal" => [[
            "POSTAL_CODE"
        ]],
        "name" => [[
            "CONTACT_POINT"
        ], ["ATTENTION"]],
        "email" => [[
            "E_MAIL"
        ]],
        "phone" => [[
            "PHONE"
        ]],
        "fax" => [[
            "FAX"
        ]],
        "url" => [[
            "URL"
        ]]
    ];
    const LOT_MULTI = [
        "lot" => [
            [
                "LOT_NUMBER",
            ]
        ],
        "title" => [
            [
                "LOT_TITLE",
            ]
        ],
        "description" => [
            [
                "LOT_DESCRIPTION" => [
                    "name" => "P",
                    "array" => [
                        [
                            [

                            ]
                        ]
                    ]
                ]
            ]
        ],
        "value" => [
            [
                "NATURE_QUANTITY_SCOPE",
                "COSTS_RANGE_AND_CURRENCY",
                "VALUE_COST" => ['attribute' => 'FMTVAL']
            ]
        ],
        "currency" => [
            [
                "NATURE_QUANTITY_SCOPE",
                "COSTS_RANGE_AND_CURRENCY" => ['attribute' => 'CURRENCY']
            ]
        ]
    ];
    const AWARD_MULTI = [
        "lot" => [
            [
                "LOT_NUMBER"
            ]
        ],
        "awarded_at" => [
            [
                "CONTRACT_AWARD_DATE" => [
                    "multiple" => [
                        'year' => [[
                            "YEAR"
                        ]],
                        'month' => [[
                            "MONTH"
                        ]],
                        'day' => [[
                            "DAY"
                        ]]
                    ]
                ]
            ],
            [
                "DATE_OF_CONTRACT_AWARD" => [
                    "multiple" => [
                        'year' => [[
                            "YEAR"
                        ]],
                        'month' => [[
                            "MONTH"
                        ]],
                        'day' => [[
                            "DAY"
                        ]]
                    ]
                ]
            ]
        ],
        "contractor" => [
            [
                "ECONOMIC_OPERATOR_NAME_ADDRESS",
                "CONTACT_DATA_WITHOUT_RESPONSIBLE_NAME" => [
                    "multiple" => self::CONTACT_MULTI
                ]
            ],
            [

                "CONTACT_DATA_WITHOUT_RESPONSIBLE_NAME_CHP" => [
                    "multiple" => self::CONTACT_MULTI
                ]
            ]
        ],
        "value" => [
            [
                "CONTRACT_VALUE_INFORMATION",
                "COSTS_RANGE_AND_CURRENCY_WITH_VAT_RATE",
                "VALUE_COST" => ["attribute" => "FMTVAL"]
            ]
        ],
        "currency" => [
            [
                "CONTRACT_VALUE_INFORMATION",
                "COSTS_RANGE_AND_CURRENCY_WITH_VAT_RATE" => ["attribute" => "CURRENCY"]
            ]
        ]
    ];

    public static function handle($x, $identity)
    {
        $data = self::defaults($x);

        if (!method_exists(get_called_class(), $identity)) {
            throw new \Exception("Unable to find parser for $identity.");
        }

        $data['identity'] = $identity;

        $langs = explode(' ',$data['lang']);
        if (in_array('EN', $langs)) {
            $data['document_url'] = $data['document_url'][array_search("EN", $langs)];
            $data['lang'] = 'EN';

        } else {
            $data['orig_lang'] = explode(' ', $data['orig_lang'])[0];
            $data['document_url'] = $data['document_url'][array_search($data['orig_lang'], $langs)];
            $data['lang'] = $data['orig_lang'];
        }
        $data = array_merge($data, self::$identity($x, $data), ['raw' => $x->find([
            "FORM_SECTION" => [
                "search" => $data['identity'],
                "attribute" => "LG",
                "for" => $data['lang']
            ],
            0 => ['xml' => true]
        ])]);

        if(isset($data['referenced']))
        {
            $y = explode('/', $data['referenced'])[0];
            $r = explode('-', explode(' ', $data['referenced'])[1])[1];
            $data['referenced'] = $r.'-'.$y;
        }

        if(isset($data['value']))
            $data['value'] = str_replace(' ', '', $data['value']);

        if(isset($data['description']))
            $data['description'] = implode("\n",array_flatten( $data['description']));
        if(isset($data['deadline']))
        {
            if(is_array($data['deadline']))
                $data['deadline'] = implode('-', $data['deadline']);
            else
                    $data['deadline'] = substr($data['deadline'], 0, 4).'-'.substr($data['deadline'], 4, 2).'-'.substr($data['deadline'], 6, 2);
        }
        $data['published_at'] = substr($data['published_at'], 0, 4).'-'.substr($data['published_at'], 4, 2).'-'.substr($data['published_at'], 6, 2);
        if(isset($data['lot'])){
            foreach($data['lot'] as $index => $lot){
                if(isset($lot['description']))
                    $data['lot'][$index]['description'] = implode("\n", array_flatten($lot['description']));
            }
        }

        return $data;
    }

    private static function defaults($x)
    {
        return [
            'ref' => $x->find([["attribute" => "DOC_ID"]]),
            'referenced' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "REF_NOTICE",
                    "NO_DOC_OJS"
                ]),
            'type' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "CODIF_DATA",
                    "TD_DOCUMENT_TYPE" => ["attribute" => "CODE"]

                ]),
            'lang' => $x->find(
                [
                    "TECHNICAL_SECTION",
                    "FORM_LG_LIST",
                ]),
            'orig_lang' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "LG_ORIG",
                ]),
            'document_url' => array_flatten($x->find(
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "URI_LIST" => [
                        "name" => "URI_DOC",
                        "array" => [
                            [
                                []
                            ]
                        ]
                    ],
                ])),

            'category' => array_flatten($x->find(
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA" => [
                        "name" => "ORIGINAL_CPV",
                        "array" => [
                            [
                                [0 => ["attribute" => "CODE"]]
                            ]
                        ]
                    ]
                ])),
            'body_url' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "IA_URL_GENERAL"
                ]),
            'tendering_url' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "IA_URL_ETENDERING"
                ]),
            'body' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "CODIF_DATA",
                    "AA_AUTHORITY_TYPE"
                ]),
            'nature' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "CODIF_DATA",
                    "NC_CONTRACT_NATURE"
                ]),
            'published_at' => $x->find(
                [
                    "CODED_DATA_SECTION",
                    "REF_OJS",
                    "DATE_PUB"
                ]),
            'deadline' => $x->find([
                "CODED_DATA_SECTION",
                "CODIF_DATA",
                "DT_DATE_FOR_SUBMISSION"
            ]),
            'value' => $x->multiple([
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "VALUES" => [
                        "search" => "VALUE",
                        "for" => "PROCUREMENT_TOTAL",
                        "attribute" => "TYPE"
                    ]
                ],
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "VALUES" => [
                        "search" => "VALUE",
                        "for" => "ESTIMATED_TOTAL",
                        "attribute" => "TYPE"
                    ]
                ]
                    ]
            ),
            'currency' => $x->multiple([
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "VALUES" => [
                        "search" => "VALUE",
                        "for" => "PROCUREMENT_TOTAL",
                        "attribute" => "TYPE"
                    ],
                    ["attribute" => "CURRENCY"]
                ],
                [
                    "CODED_DATA_SECTION",
                    "NOTICE_DATA",
                    "VALUES" => [
                        "search" => "VALUE",
                        "for" => "ESTIMATED_TOTAL",
                        "attribute" => "TYPE"
                    ],
                    ["attribute" => "CURRENCY"]
                ]]
            ),
        ];
    }

    #region R209

    private static function R209($x, $data)
    {
        $r = $x->find([

            "FORM_SECTION" => [
                "search" => $data['identity'],
                "attribute" => "LG",
                "for" => $data['lang']
            ],
            0 => [
                'multiple' => [
                    "contacts" => [
                        [
                            "CONTRACTING_BODY",
                            "ADDRESS_CONTRACTING_BODY" => [
                                "multiple" => self::CONTACT_MULTI
                            ]
                        ]
                    ],
                    "title" => [
                        [
                            "OBJECT_CONTRACT",
                            "TITLE",
                            "P"
                        ]
                    ],
                    "description" => [
                        [
                            "OBJECT_CONTRACT",
                            "SHORT_DESCR" => [
                                "name" => "P",
                                "array" => [
                                    [
                                        [0 => []]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'deadline' => [
                        [
                            "PROCEDURE",
                            "DATE_RECEIPT_TENDERS"
                        ]
                    ],
                    'lot' => [
                        [
                            "OBJECT_CONTRACT" => [
                                'name' => "OBJECT_DESCR",
                                "array" => [
                                    "title" => [
                                        [
                                            "TITLE",
                                            "P"
                                        ],
                                        [
                                            "SHORT_DESCR",
                                            "P" => ["number" => 0]
                                        ]
                                    ],
                                    "description" => [
                                        [
                                            "SHORT_DESCR" => [
                                                "name" => "P",
                                                "array" => [
                                                    [
                                                        [

                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                    "duration" => [
                                        [
                                            "DURATION"
                                        ]
                                    ],
                                    "duration_type" => [
                                        [
                                            "DURATION" => ["attribute" => "TYPE"]
                                        ]
                                    ],
                                    "value" => [
                                        [
                                            "VAL_TOTAL"
                                        ],
                                        [
                                            "VAL_OBJECT"
                                        ]
                                    ],
                                    "currency" => [
                                        [
                                            "VAL_TOTAL" => ["attribute" => "CURRENCY"]
                                        ],
                                        [
                                            "VAL_OBJECT" => ["attribute" => "CURRENCY"]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "award" => [
                        [
                            0 => ["name" => "AWARD_CONTRACT", "array" => [
                                "lot" => [
                                    [
                                        "LOT_NO" => []
                                    ]
                                ],
                                "awarded_at" => [
                                    [
                                        "AWARDED_CONTRACT",
                                        "DATE_CONCLUSION_CONTRACT"

                                    ]
                                ],
                                "contractor" => [
                                    [
                                        "AWARDED_CONTRACT",
                                        "CONTRACTOR",
                                        "ADDRESS_CONTRACTOR" => ["multiple" => self::CONTACT_MULTI]
                                    ],
                                    [
                                        "AWARDED_CONTRACT",
                                        "CONTRACTORS",
                                        "CONTRACTOR",
                                        "ADDRESS_CONTRACTOR" => ["multiple" => self::CONTACT_MULTI]
                                    ]
                                ],
                                "value" => [
                                    [
                                        "AWARDED_CONTRACT",
                                        "VAL_TOTAL"
                                    ]
                                ],
                                "currency" => [
                                    [
                                        "AWARDED_CONTRACT",
                                        "VAL_TOTAL" => ["attribute" => "CURRENCY"]
                                    ]
                                ]
                            ]]
                        ]
                    ]

                ]
            ]
        ]);
        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => [
                "search" => $data['identity'],
                "attribute" => "LG",
                "for" => $data['lang']
            ],
            "CONTRACTING_BODY",
            "ADDRESS_FURTHER_INFO" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r['contacts'][] = array_merge($f, ['type' => 'f']);
        }

        if ($p = $x->find([
            "FORM_SECTION" => [
                "search" => $data['identity'],
                "attribute" => "LG",
                "for" => $data['lang']
            ],
            "CONTRACTING_BODY",
            "ADDRESS_PARTICIPATION" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r['contacts'][] = array_merge($p, ['type' => 'p']);
        }
        return $r;
    }

    private static function F01_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F02_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F03_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F04_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F05_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F06_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F07_2014($x, $data)
    {
        return self::R209($x, $data);
    }

//    // TODO REMOVE THIS ???
    private static function F08_2014($x, $data)
    {
        return self::R209($x, $data);
    }
//    // TODO REMOVE THIS ???
//    private static function F09_2014($x, $data){
//        return self::R209($x, $data);
//    }
//    // TODO REMOVE THIS ???
//    private static function F10_2014($x, $data){
//        return self::R209($x, $data);
//    }
//    // TODO REMOVE THIS ???
//    private static function F11_2014($x, $data){
//        return self::R209($x, $data);
//    }
    private static function F12_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F13_2014($x, $data)
    {
        return self::R209($x, $data);
        // TODO winners
    }

    private static function F14_2014($x, $data)
    {
        return self::R209($x, $data);
        // TODO changes
    }

    private static function F15_2014($x, $data)
    {
        return self::R209($x, $data);
    }
//    // TODO REMOVE THIS ???
//    private static function F16_2014($x, $data){
//        return self::R209($x, $data);
//    }
//    // TODO REMOVE THIS ???
//    private static function F17_2014($x, $data){
//        return self::R209($x, $data);
//    }
//    // TODO REMOVE THIS ???
//    private static function F18_2014($x, $data){
//        return self::R209($x, $data);
//    }
//    // TODO REMOVE THIS ???
//    private static function F19_2014($x, $data){
//        return self::R209($x, $data);
//    }
//    // TODO REMOVE THIS ???
    private static function F20_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F21_2014($x, $data)
    {
        return self::R209($x, $data);
        // TODO contacts, lots, awards // Can be anything
    }

    private static function F22_2014($x, $data)
    {
        return self::R209($x, $data);
        // TODO contacts, lots, awards // Can be anything
    }

    private static function F23_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F24_2014($x, $data)
    {
        return self::R209($x, $data);
    }

    private static function F25_2014($x, $data)
    {
        return self::R209($x, $data);
    }


    #endregion

    #region R208

    private static function CONCESSION($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONCESSION" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_CONCESSION",
                        "NAME_ADDRESSES_CONTACT_CONCESSION",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONCESSION",
                        "DESCRIPTION_CONCESSION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONCESSION",
                        "DESCRIPTION_CONCESSION",
                        "DESCRIPTION_OF_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURES_CONCESSION",
                        "ADMINISTRATIVE_INFORMATION_CONCESSION",
                        "TIME_LIMIT_CHP" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONCESSION",
            "AUTHORITY_CONCESSION",
            "NAME_ADDRESSES_CONTACT_CONCESSION",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONCESSION",
            "AUTHORITY_CONCESSION",
            "NAME_ADDRESSES_CONTACT_CONCESSION",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONCESSION",
            "AUTHORITY_CONCESSION",
            "NAME_ADDRESSES_CONTACT_CONCESSION",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;

    }

    private static function CONTRACT_AWARD_DEFENCE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_AWARD_DEFENCE" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_INFORMATION_CONTRACT_AWARD_DEFENCE",
                        "NAME_ADDRESSES_CONTACT_CONTRACT_AWARD",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_AWARD_NOTICE_DEFENCE",
                        "DESCRIPTION_AWARD_NOTICE_INFORMATION_DEFENCE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_AWARD_NOTICE_DEFENCE",
                        "DESCRIPTION_AWARD_NOTICE_INFORMATION_DEFENCE",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                "award" => [
                    [
                        0 => [
                            "name" => "AWARD_OF_CONTRACT_DEFENCE",
                            "array" => self::AWARD_MULTI
                        ]
                    ]
                ]
            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        return $r;
        // TODO CHECK AWARD
    }

    private static function CONTRACT_AWARD($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_AWARD" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_INFORMATION_CONTRACT_AWARD",
                        "NAME_ADDRESSES_CONTACT_CONTRACT_AWARD",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_AWARD_NOTICE",
                        "DESCRIPTION_AWARD_NOTICE_INFORMATION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_AWARD_NOTICE",
                        "DESCRIPTION_AWARD_NOTICE_INFORMATION",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],


                "award" => [
                    [
                        0 => [
                            "name" => "AWARD_OF_CONTRACT",
                            "array" => self::AWARD_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        return $r;
        // TODO CHECK AWARD
    }

    private static function CONTRACT_AWARD_UTILITIES($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_AWARD_UTILITIES" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_ENTITY_CONTRACT_AWARD_UTILITIES",
                        "NAME_ADDRESSES_CONTACT_CONTRACT_AWARD_UTILITIES",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_AWARD_UTILITIES",
                        "DESCRIPTION_CONTRACT_AWARD_UTILITIES",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_AWARD_UTILITIES",
                        "DESCRIPTION_CONTRACT_AWARD_UTILITIES",
                        "SHORT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                "award" => [
                    [
                        "AWARD_CONTRACT_CONTRACT_AWARD_UTILITIES" => [
                            "name" => "AWARD_AND_CONTRACT_VALUE",
                            "array" => self::AWARD_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        return $r;
        // TODO CHECK AWARD
    }

    private static function CONTRACT_CONCESSIONAIRE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE" => ['multiple' => [
                "contacts" => [
                    [
                        "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
                        "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_NOTICE",
                        "OBJECT_CONTRACT_NOTICE_DESCRIPTION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_NOTICE",
                        "OBJECT_CONTRACT_NOTICE_DESCRIPTION",
                        "SHORT_DESCRIPTION_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURES_CONTRACT_NOTICE",
                        "ADMINISTRATIVE_INFORMATION_CONTRACT_CONCESSIONAIRE",
                        "F11_TIME_LIMIT_TYPE",
                        "TIME_LIMIT" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE",
            "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE",
            "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE",
            "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function CONTRACT_CONCESSIONAIRE_DEFENCE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE_DEFENCE" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_INFORMATION_CONTRACT_SUB_DEFENCE",
                        "NAME_ADDRESSES_CONTACT_CONTRACT",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_SUB_DEFENCE",
                        "DESCRIPTION_CONTRACT_SUB_DEFENCE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_SUB_DEFENCE",
                        "DESCRIPTION_CONTRACT_SUB_DEFENCE",
                        "SHORT_DESCRIPTION_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURE_DEFINITION_CONTRACT_SUB_DEFENCE",
                        "ADMINISTRATIVE_INFORMATION_CONTRACT_SUB_NOTICE_DEFENCE",
                        "RECEIPT_LIMIT_DATE" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE_DEFENCE",
            "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE_DEFENCE",
            "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_CONCESSIONAIRE_DEFENCE",
            "PUBLIC_WORKS_CONCESSIONAIRE_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_CONTRACT_CONCESSIONAIRE",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function CONTRACT_DEFENCE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_DEFENCE" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_INFORMATION_DEFENCE",
                        "NAME_ADDRESSES_CONTACT_CONTRACT",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_DEFENCE",
                        "DESCRIPTION_CONTRACT_INFORMATION_DEFENCE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_DEFENCE",
                        "DESCRIPTION_CONTRACT_INFORMATION_DEFENCE",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURE_DEFINITION_CONTRACT_NOTICE_DEFENCE",
                        "ADMINISTRATIVE_INFORMATION_CONTRACT_NOTICE_DEFENCE",
                        "RECEIPT_LIMIT_DATE" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ],
                'lot' => [
                    [

                        "OBJECT_CONTRACT_INFORMATION_DEFENCE",
                        "DESCRIPTION_CONTRACT_INFORMATION_DEFENCE",
                        "F17_DIVISION_INTO_LOTS",
                        "F17_DIV_INTO_LOT_YES" => [
                            "name" => "F17_ANNEX_B",
                            "array" => self::LOT_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_DEFENCE",
            "CONTRACTING_AUTHORITY_INFORMATION_DEFENCE",
            "NAME_ADDRESSES_CONTACT_CONTRACT",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_DEFENCE",
            "CONTRACTING_AUTHORITY_INFORMATION_DEFENCE",
            "NAME_ADDRESSES_CONTACT_CONTRACT",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_DEFENCE",
            "CONTRACTING_AUTHORITY_INFORMATION_DEFENCE",
            "NAME_ADDRESSES_CONTACT_CONTRACT",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function CONTRACT($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_INFORMATION",
                        "NAME_ADDRESSES_CONTACT_CONTRACT",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION",
                        "DESCRIPTION_CONTRACT_INFORMATION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION",
                        "DESCRIPTION_CONTRACT_INFORMATION",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURE_DEFINITION_CONTRACT_NOTICE",
                        "ADMINISTRATIVE_INFORMATION_CONTRACT_NOTICE",
                        "RECEIPT_LIMIT_DATE" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ],
                'lot' => [
                    [

                        "OBJECT_CONTRACT_INFORMATION",
                        "DESCRIPTION_CONTRACT_INFORMATION",
                        "F02_DIVISION_INTO_LOTS",
                        "F02_DIV_INTO_LOT_YES" => [
                            "name" => "F02_ANNEX_B",
                            "array" => self::LOT_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT",
            "CONTRACTING_AUTHORITY_INFORMATION",
            "NAME_ADDRESSES_CONTACT_CONTRACT",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT",
            "CONTRACTING_AUTHORITY_INFORMATION",
            "NAME_ADDRESSES_CONTACT_CONTRACT",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT",
            "CONTRACTING_AUTHORITY_INFORMATION",
            "NAME_ADDRESSES_CONTACT_CONTRACT",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function CONTRACT_MOVE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_MOVE" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_CONTRACT_MOVE",
                        "NAME_ADDRESSES_CONTACT_MOVE",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_MOVE",
                        "DESCRIPTION_CONTRACT_MOVE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_MOVE",
                        "DESCRIPTION_CONTRACT_MOVE",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],
                "award" => [
                    [
                        0 => [
                            "name" => "AWARD_CONTRACT_MOVE",
                            "array" => self::AWARD_MULTI
                        ]
                    ]
                ]
            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_MOVE",
            "AUTHORITY_CONTRACT_MOVE",
            "NAME_ADDRESSES_CONTACT_MOVE",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_MOVE",
            "AUTHORITY_CONTRACT_MOVE",
            "NAME_ADDRESSES_CONTACT_MOVE",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_MOVE",
            "AUTHORITY_CONTRACT_MOVE",
            "NAME_ADDRESSES_CONTACT_MOVE",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;

        // TODO CHECK AWARDS
    }

    private static function CONTRACT_UTILITIES($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_INFO",
                        "NAME_ADDRESSES_CONTACT_CONTRACT_UTILITIES",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_UTILITIES",
                        "CONTRACT_OBJECT_DESCRIPTION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_UTILITIES",
                        "CONTRACT_OBJECT_DESCRIPTION",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURE_DEFINITION_CONTRACT_NOTICE_UTILITIES",
                        "ADMINISTRATIVE_INFORMATION_CONTRACT_UTILITIES",
                        "RECEIPT_LIMIT_DATE" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ],
                'lot' => [
                    [
                        "OBJECT_CONTRACT_INFORMATION_CONTRACT_UTILITIES",
                        "CONTRACT_OBJECT_DESCRIPTION",
                        "F05_DIVISION_INTO_LOTS",
                        "F05_DIV_INTO_LOT_YES" => [
                            "name" => "F05_ANNEX_B",
                            "array" => self::LOT_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES",
            "CONTRACTING_AUTHORITY_INFO",
            "NAME_ADDRESSES_CONTACT_CONTRACT_UTILITIES",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES",
            "CONTRACTING_AUTHORITY_INFO",
            "NAME_ADDRESSES_CONTACT_CONTRACT_UTILITIES",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES",
            "CONTRACTING_AUTHORITY_INFO",
            "NAME_ADDRESSES_CONTACT_CONTRACT_UTILITIES",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function DESIGN_CONTEST($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_DESIGN_CONTEST" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_ENTITY_DESIGN_CONTEST",
                        "NAME_ADDRESSES_CONTACT_DESIGN_CONTEST",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_DESIGN_CONTEST",
                        "TITLE_DESIGN_CONTACT_NOTICE",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_DESIGN_CONTEST",
                        "SHORT_DESCRIPTION_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURES_DESIGN_CONTEST",
                        "ADMINISTRATIVE_INFORMATION_DESIGN_CONTEST_NOTICE",
                        "TIME_LIMIT_CHP" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ],

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES",
            "AUTHORITY_ENTITY_DESIGN_CONTEST",
            "NAME_ADDRESSES_CONTACT_DESIGN_CONTEST",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES",
            "AUTHORITY_ENTITY_DESIGN_CONTEST",
            "NAME_ADDRESSES_CONTACT_DESIGN_CONTEST",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT_UTILITIES",
            "AUTHORITY_ENTITY_DESIGN_CONTEST",
            "NAME_ADDRESSES_CONTACT_DESIGN_CONTEST",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function PERIODIC_INDICATIVE_UTILITIES($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PERIODIC_INDICATIVE_UTILITIES" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_PERIODIC_INDICATIVE",
                        "NAME_ADDRESSES_CONTACT_PERIODIC_INDICATIVE_UTILITIES",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_CONTRACT_PERIODIC_INDICATIVE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_CONTRACT_PERIODIC_INDICATIVE",
                        "DESCRIPTION_OF_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],
                'lot' => [
                    [
                        "OBJECT_CONTRACT_PERIODIC_INDICATIVE" => [
                            "name" => "ANNEX_B_INFORMATION_LOTS_PERIODIC_INDICATIVE",
                            "array" => self::LOT_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PERIODIC_INDICATIVE_UTILITIES",
            "AUTHORITY_PERIODIC_INDICATIVE",
            "NAME_ADDRESSES_CONTACT_PERIODIC_INDICATIVE_UTILITIES",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PERIODIC_INDICATIVE_UTILITIES",
            "AUTHORITY_PERIODIC_INDICATIVE",
            "NAME_ADDRESSES_CONTACT_PERIODIC_INDICATIVE_UTILITIES",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PERIODIC_INDICATIVE_UTILITIES",
            "AUTHORITY_PERIODIC_INDICATIVE",
            "NAME_ADDRESSES_CONTACT_PERIODIC_INDICATIVE_UTILITIES",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function PRIOR_INFORMATION_DEFENCE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION_DEFENCE" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_PRIOR_INFORMATION_DEFENCE",
                        "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_WORKS_SUPPLIES_SERVICES_PRIOR_INFORMATION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_WORKS_SUPPLIES_SERVICES_PRIOR_INFORMATION",
                        "DESCRIPTION_OF_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],
                'lot' => [
                    [
                        "OBJECT_WORKS_SUPPLIES_SERVICES_PRIOR_INFORMATION",
                        "QUANTITY_SCOPE_WORKS_DEFENCE",
                        "F16_DIVISION_INTO_LOTS",
                        "F16_DIV_INTO_LOT_YES" => [
                            "name" => "LOT_PRIOR_INFORMATION",
                            "array" => self::LOT_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION_DEFENCE",
            "AUTHORITY_PRIOR_INFORMATION_DEFENCE",
            "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION_DEFENCE",
            "AUTHORITY_PRIOR_INFORMATION_DEFENCE",
            "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION_DEFENCE",
            "AUTHORITY_PRIOR_INFORMATION_DEFENCE",
            "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function PRIOR_INFORMATION($x, $data)
    {
        $cType = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION" => ['attribute' => "CTYPE"]
        ]);
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_PRIOR_INFORMATION",
                        "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_{$cType}_PRIOR_INFORMATION",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_{$cType}_PRIOR_INFORMATION",
                        "DESCRIPTION_OF_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],
                'lot' => [
                    [
                        "OBJECT_{$cType}_PRIOR_INFORMATION",
                        "F01_DIVISION_INTO_LOTS",
                        "F01_DIV_INTO_LOT_YES" => [
                            "name" => "F01_ANNEX_B",
                            "array" => self::LOT_MULTI
                        ]
                    ]
                ]

            ]]
        ]);

        if(!count($r['contacts']))
            dd($data);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION",
            "AUTHORITY_PRIOR_INFORMATION",
            "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION",
            "AUTHORITY_PRIOR_INFORMATION",
            "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION",
            "AUTHORITY_PRIOR_INFORMATION",
            "NAME_ADDRESSES_CONTACT_PRIOR_INFORMATION",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
    }

    private static function PRIOR_INFORMATION_MOVE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_PRIOR_INFORMATION_MOVE" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_PI_MOVE",
                        "NAME_ADDRESSES_CONTACT_MOVE",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_PI_MOVE",
                        "DESCRIPTION_PI_MOVE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_PI_MOVE",
                        "DESCRIPTION_PI_MOVE",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURE_PI_MOVE",
                        "ADMINISTRATIVE_INFORMATION_PI_MOVE",
                        "RECEIPT_LIMIT_DATE" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ],

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT",
            "AUTHORITY_PI_MOVE",
            "NAME_ADDRESSES_CONTACT_MOVE",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT",
            "AUTHORITY_PI_MOVE",
            "NAME_ADDRESSES_CONTACT_MOVE",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_CONTRACT",
            "AUTHORITY_PI_MOVE",
            "NAME_ADDRESSES_CONTACT_MOVE",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
        // TODO ADD LOTS ???
    }

    private static function QUALIFICATION_SYSTEM_UTILITIES($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_QUALIFICATION_SYSTEM_UTILITIES" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_ENTITY_QUALIFICATION_SYSTEM",
                        "NAME_ADDRESSES_CONTACT_QUALIFICATION_SYSTEM_UTILITIES",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_QUALIFICATION_SYSTEM",
                        "TITLE_QUALIFICATION_SYSTEM",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_QUALIFICATION_SYSTEM",
                        "DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],
            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_QUALIFICATION_SYSTEM_UTILITIES",
            "CONTRACTING_ENTITY_QUALIFICATION_SYSTEM",
            "NAME_ADDRESSES_CONTACT_QUALIFICATION_SYSTEM_UTILITIES",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_QUALIFICATION_SYSTEM_UTILITIES",
            "CONTRACTING_ENTITY_QUALIFICATION_SYSTEM",
            "NAME_ADDRESSES_CONTACT_QUALIFICATION_SYSTEM_UTILITIES",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_QUALIFICATION_SYSTEM_UTILITIES",
            "CONTRACTING_ENTITY_QUALIFICATION_SYSTEM",
            "NAME_ADDRESSES_CONTACT_QUALIFICATION_SYSTEM_UTILITIES",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
        // TODO add lots  ???
    }

    private static function RESULT_DESIGN_CONTEST($x, $data)
    {
        $r = [
            "award" => $x->find([

                "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
                "FD_RESULT_DESIGN_CONTEST",
                "RESULTS_CONTEST_RESULT_DESIGN_CONTEST" => [
                    "name" => "RESULT_CONTEST",
                    "array" => [
                        "contractor" => [
                            [
                                "AWARD_PRIZES",
                                "NAME_ADDRESS_WINNER",
                                "CONTACT_DATA_WITHOUT_RESPONSIBLE_NAME" => [
                                    "multiple" => self::CONTACT_MULTI
                                ]
                            ]
                        ]
                    ]
                ]
            ])
        ];
        // TODO check awarded

        return $r;
    }

    private static function SIMPLIFIED_CONTRACT($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_SIMPLIFIED_CONTRACT" => ['multiple' => [
                "contacts" => [
                    [
                        "AUTHORITY_ENTITY_SIMPLIFIED_CONTRACT_NOTICE",
                        "NAME_ADDRESSES_CONTACT_SIMPLIFIED_CONTRACT",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_SIMPLIFIED_CONTRACT_NOTICE",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_SIMPLIFIED_CONTRACT_NOTICE",
                        "SHORT_DESCRIPTION_CONTRACT" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],

                'deadline' => [
                    [
                        "PROCEDURES_SIMPLIFIED_CONTRACT_NOTICE",
                        "ADMINISTRATIVE_INFORMATION_SIMPLIFIED_CONTRACT",
                        "TIME_LIMIT_CHP" => ['multiple' => [
                            "year" => [["YEAR"]],
                            "month" => [["MONTH"]],
                            "day" => [["DAY"]],
                        ]]
                    ]
                ],

            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_SIMPLIFIED_CONTRACT",
            "AUTHORITY_ENTITY_SIMPLIFIED_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_SIMPLIFIED_CONTRACT",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_SIMPLIFIED_CONTRACT",
            "AUTHORITY_ENTITY_SIMPLIFIED_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_SIMPLIFIED_CONTRACT",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_SIMPLIFIED_CONTRACT",
            "AUTHORITY_ENTITY_SIMPLIFIED_CONTRACT_NOTICE",
            "NAME_ADDRESSES_CONTACT_SIMPLIFIED_CONTRACT",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
        // TODO ADD LOTS ???
    }

    private static function VOLUNTARY_EX_ANTE_TRANSPARENCY_NOTICE($x, $data)
    {
        $r = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_VOLUNTARY_EX_ANTE_TRANSPARENCY_NOTICE" => ['multiple' => [
                "contacts" => [
                    [
                        "CONTRACTING_AUTHORITY_VEAT",
                        "NAME_ADDRESSES_CONTACT_VEAT",
                        "CA_CE_CONCESSIONAIRE_PROFILE" => ["multiple" => self::CONTACT_MULTI]
                    ]
                ],

                'title' => [
                    [
                        "OBJECT_VEAT",
                        "DESCRIPTION_VEAT",
                        "TITLE_CONTRACT",
                        "P"
                    ]
                ],
                'description' => [
                    [
                        "OBJECT_VEAT",
                        "DESCRIPTION_VEAT",
                        "SHORT_CONTRACT_DESCRIPTION" => [
                            "name" => "P",
                            "array" => [
                                [
                                    [0 => []]
                                ]
                            ]
                        ]
                    ]
                ],
                'award' => [
                    [
                        0 => ["name" => "AWARD_OF_CONTRACT_DEFENCE", "array" => self::AWARD_MULTI]
                    ]
                ]
            ]]
        ]);

        $r['contacts'] = [array_merge($r['contacts'], ['type' => 'c'])];

        if ($f = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_VOLUNTARY_EX_ANTE_TRANSPARENCY_NOTICE",
            "CONTRACTING_AUTHORITY_VEAT",
            "NAME_ADDRESSES_CONTACT_VEAT",
            "FURTHER_INFORMATION",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($f, ['type' => 'f']);
        }
        if ($d = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_VOLUNTARY_EX_ANTE_TRANSPARENCY_NOTICE",
            "CONTRACTING_AUTHORITY_VEAT",
            "NAME_ADDRESSES_CONTACT_VEAT",
            "SPECIFICATIONS_AND_ADDITIONAL_DOCUMENTS",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($d, ['type' => 'd']);
        }
        if ($p = $x->find([
            "FORM_SECTION" => ["search" => $data['identity'], "attribute" => "LG", "for" => $data['lang']],
            "FD_VOLUNTARY_EX_ANTE_TRANSPARENCY_NOTICE",
            "CONTRACTING_AUTHORITY_VEAT",
            "NAME_ADDRESSES_CONTACT_VEAT",
            "TENDERS_REQUESTS_APPLICATIONS_MUST_BE_SENT_TO",
            "CONTACT_DATA" => [
                "multiple" => self::CONTACT_MULTI
            ]
        ])) {
            $r["contacts"][] = array_merge($p, ['type' => 'p']);
        }

        return $r;
        // TODO check awards
    }

    #endregion

    #region Other

    private static function OTH_NOT($x, $data)
    {
        return [];
    }

    private static function EEIG($x, $data)
    {
        return [];
    }

    private static function BUYER_PROFILE($x, $data)
    {
        return [];
    }

    #endregion
}