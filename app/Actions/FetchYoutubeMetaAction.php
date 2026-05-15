<?php

namespace App\Actions;

use DateInterval;
use Google\Client;
use Google\Service\YouTube;

class FetchYoutubeMetaAction
{
    public function handle(string $videoId): ?array
    {
        $client = new Client;
        $client->setDeveloperKey(config('services.youtube.key'));

        $youtube = new YouTube($client);
        $response = $youtube->videos->listVideos('snippet,contentDetails,statistics', [
            'id' => $videoId,
        ]);

        if (count($response->getItems()) === 0) {
            return null;
        }

        $video          = $response->getItems()[0];
        $snippet        = $video->getSnippet();
        $contentDetails = $video->getContentDetails();
        $statistics     = $video->getStatistics();

        $duration = new DateInterval($contentDetails->getDuration());
        $seconds  = ($duration->h * 3600) + ($duration->i * 60) + $duration->s;

        return [
            'video_id'    => $videoId,
            'title'       => $snippet->getTitle(),
            'description' => $snippet->getDescription(),
            'thumbnail'   => $snippet->getThumbnails()->getDefault()->getUrl(),
            'duration'    => gmdate(($seconds >= 3600 ? 'H:i:s' : 'i:s'), $seconds),
            'channel'     => $snippet->getChannelTitle(),
            'views'       => $statistics->getViewCount(),
        ];
    }
}
