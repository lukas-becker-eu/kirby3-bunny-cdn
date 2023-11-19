<?php
/*
https://docs.bunny.net/reference/pullzonepublic_purgecachepostbytag
*/

use Kirby\Exception\NotFoundException;

Kirby::plugin('lukasbecker/kirby3-bunny-cdn', [
  'options' => [
    'id' => '',
    'accessKey' => '',
    'url' => 'https://api.bunny.net/pullzone/{id}/purgeCache',
    'trigger' => '/purge-cache',
    // 'secret' => '12345' To do: Add secret to trigger url
  ],
  'routes' => function ($kirby) {
    return [
      [
        'pattern' => option('lukasbecker.kirby3-bunny-cdn.trigger', '/purge-cache'),
        'method' => 'GET|POST',
        'action' => function () {
          $alert = null;
          $key = option('lukasbecker.kirby3-bunny-cdn.accessKey') ?? null;
          $id = option('lukasbecker.kirby3-bunny-cdn.id') ?? null;
          $url = option('lukasbecker.kirby3-bunny-cdn.url') ?? null;
          if (!empty($key) && !empty($id)) {
            $url = Str::replace($url, '{id}', $id);
            try {
              $options = [
                'method'  => 'POST',
                'headers' => [
                  'AccessKey' => $key,
                  'content-type' => 'application/json',
                ]
              ];
              $response = Remote::request($url, $options);
            } catch (Exception $error) {
              if (option('debug')) :
                $alert['error'] = 'Internal Server Error: <strong>' . $error->getMessage() . '</strong>';
              else :
                $alert['error'] = 'Internal Server Error';
              endif;
            }

            if ($response->code() === 204) {
              return new Response('The cache was successfully purged', 'text/html');
            } elseif ($response->code() === 401) {
              throw new NotFoundException('The request authorization failed');
            } elseif ($response->code() === 404) {
              throw new NotFoundException('The Pull Zone with the requested ID does not exist');
            } elseif ($response->code() === 500) {
              throw new NotFoundException('Internal Server Error');
            } else {
              return false;
            }
          }
        }
      ],
    ];
  },
  'hooks' => [
    'route:before' => function ($route, $path, $method) {
      if ($path === option('lukasbecker.kirby3-bunny-cdn.trigger', '/purge-cache') && !kirby()->user()) {
        die('Nope');
      }
    }
  ]
]);