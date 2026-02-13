<?php

return [
    'host' => env('ELASTICSEARCH_HOST', 'http://elasticsearch:9200'),
    'index' => env('ELASTICSEARCH_INDEX', 'products'),
];
