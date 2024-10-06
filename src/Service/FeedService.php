<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Feed;
use App\Model\Table\FeedsTable;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Xml;
use RuntimeException;
use SimpleXMLElement;

class FeedService
{
    use LocatorAwareTrait;

    private FeedsTable $feeds;
    private Client $client;
    /**
     * Timeout to fetch feeds in seconds
     */
    private int $fetchTimeout = 2;

    public function __construct(Client $client)
    {
        $this->feeds = $this->fetchTable('Feeds');
        $this->client = $client;
    }

    /**
     * Fetch URL with the cake HTTP client.
     */
    protected function fetchUrl(string $url): Response
    {
        // TODO prevent ssrf on internal networks.
        $res = $this->client->get($url, [], [
            'timeout' => $this->fetchTimeout,
        ]);
        if (!$res->isOk()) {
            throw new BadRequestException("Could not fetch $url");
        }

        return $res;
    }

    /**
    * Import new FeedItems for all the unique items in a feed's current response
    */
    public function refreshFeed(Feed $feed): void
    {
        $res = $this->fetchUrl($feed->url);
        $items = $this->parseResponse($res, $feed);
        $this->saveNewItems($items);
    }

    protected function parseResponse(Response $res, Feed $feed): array
    {
        $contentType = $res->getHeaderLine('Content-Type');
        $colonPos = strpos($contentType, ';');
        if ($colonPos !== false) {
            $contentType = substr($contentType, $colonPos);
        }
        $body = (string)$res->getBody();
        // No items in an empty response.
        if (!$body) {
            return [];
        }
        $readValue = function (SimpleXMLElement $elem, string $xpath): string {
            $found = $elem->xpath($xpath);
            if ($found === false || $found === null) {
                return '';
            }
            return (string)$found[0];
        };
        switch ($contentType) {
            case 'application/rss':
            case 'application/rss+xml':
                $items = [];
                /** @var \SimpleXMLElement $xml */
                $xml = Xml::build($body);
                $xmlItems = $xml->xpath('/channel/item');
                // No items in the feed, abort
                if (!$xmlItems) {
                    return [];
                }
                foreach ($xmlItems as $xmlItem) {
                    $item = $this->feeds->FeedItems->newEmptyEntity();
                    $item->guid = $readValue($xmlItem, '/guid');
                    $item->title = $readValue($xmlItem, '/title');
                    $item->summary = $readValue($xmlItem, '/description');
                    $item->published_at = new DateTime($readValue($xmlItem, '/pubDate'));
                    $item->feed_id = $feed->id;

                    $items[] = $item;
                }

                return $items;
                break;
            default:
                throw RuntimeException("Unknown content type of $contentType");
        }
    }

    protected function saveNewItems(array $items): void
    {
        $this->feeds->getConnection()->transactional(function () use ($items) {
            foreach ($items as $item) {
                $existing = $this->feeds->FeedItems
                    ->find()
                    ->where([
                        'FeedItems.feed_id' => $item->feed_id,
                        'FeedItems.guid' => $item->guid,
                    ])
                    ->first();
                if ($existing) {
                    $item->id = $existing->id;
                }
                // TODO skip noop item updates.
                $this->feeds->FeedItems->save($item);
            }
        });
    }
}
