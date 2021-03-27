<?php
$this->disableAutoLayout();

$this->response = $this->response->withType('application/manifest+json');

echo json_encode([
    'background_color' => '#a848a8',
    'display' => 'standalone',
    'icons' => [
        [
            'src' => $this->Url->assetUrl('img/docket-logo.png'),
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'maskable any',
        ],
    ],
    'name' => 'Docket',
    'description' => 'Your personal todo list',
    'short_name' => 'Docket',
    'start_url' => $this->Url->build(['_name' => 'tasks:today']),
]);
