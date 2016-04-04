<?php

namespace SmartGrid\data;

class DataProvider
{
    protected $options = [
        'query' => '',
        'table' => '',
        'pagination' => [
            'pageSize' => 10,
            'total' => 0
        ],
        'sort' => [
            'defaultOrder' => []
        ]
    ];
    protected $result = [
        'pagination' => [
            'pageSize' => null,
            'currentPage' => null,
            'total' => null
        ],
        'defaultOrder' => [],
        'records' => []
    ];

    public function run()
    {
        return;
    }
}