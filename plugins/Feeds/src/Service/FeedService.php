<?php
declare(strict_types=1);

namespace Feeds\Service;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Cake\Utility\Xml;
use DOMDocument;
use DOMXPath;
use Exception;
use Feeds\Model\Entity\Feed;
use Feeds\Model\Table\FeedsTable;
use Laminas\Diactoros\Uri;
use RuntimeException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class FeedService
{
    use LocatorAwareTrait;

    private FeedsTable $feeds;
    private Client $client;
    private HtmlSanitizerInterface $cleaner;

    /**
     * Timeout to fetch feeds in seconds
     */
    private int $fetchTimeout = 2;

    private const SUMMARY_LENGTH = 475;

    public function __construct(Client $client, HtmlSanitizerInterface $cleaner)
    {
        /** @var \Feeds\Model\Table\FeedsTable $this->feeds */
        $this->feeds = $this->fetchTable('Feeds.Feeds');
        $this->client = $client;
        $this->cleaner = $cleaner;
    }

    /**
     * Fetch URL with the cake HTTP client.
     */
    protected function fetchUrl(string $url): Response
    {
        // TODO prevent ssrf on internal networks.
        $res = $this->client->get($url, [], [
            'timeout' => $this->fetchTimeout,
            'redirect' => 3,
        ]);
        if (!$res->isOk()) {
            throw new BadRequestException("Could not fetch $url");
        }

        return $res;
    }

    /**
     * Fetch a URL and parse the resulting HTML page for any /head/link elements
     * that quack like a known feed type.
     *
     * @return array<\App\Model\Entity\Feed> Returns a list of pending Feed entities.
     */
    public function discoverFeeds(string $url): array
    {
        // TODO there should be rate limiting on URLs to limit
        // feed discovery from blasting a site.

        // Fetch the URL
        $response = $this->fetchUrl($url);

        /** @var array<\App\Model\Entity\Feed> $feeds */
        $feeds = [];

        $contentType = $this->getContentType($response);
        $feedTypes = ['application/atom+xml', 'application/xml', 'text/xml', 'application/rss', 'application/rss+xml'];

        // Feed URL provided
        if (in_array($contentType, $feedTypes)) {
            $feeds[] = new Feed([
                'default_alias' => $url,
                'url' => $url,
                'favicon_url' => '',
            ]);

            return $feeds;
        }

        // If we didn't get a feed URL, or an HTML page abort.
        if ($contentType != 'text/html') {
            throw new RuntimeException('That URL is not an HTML page. No feed could be found.');
        }
        try {
            // Turn off errors because domdocument complains on html5
            $dom = new DOMDocument();
            $dom->loadHtml($response->getBody() . '', LIBXML_NOERROR);
        } catch (Exception $e) {
            throw new RuntimeException('That URL contains invalid HTML and could not be processed.', 0, $e);
        }

        // TODO url isn't going to reflect domain redirects.
        // If sites don't have absolute URLs, the generated paths will be wrong.
        // See if this is a problem.

        // No user/pass support yet.
        $uri = new Uri($url);

        // parse the HTML page looking for link elements
        $xpath = new DOMXPath($dom);

        // Look for a favicon url
        $favicon = $this->findFavicon($xpath);
        if ($favicon !== null && $favicon[0] == '/') {
            $favicon = $this->applyBaseUrl($favicon, $uri);
        }

        // Read the page title for feed alias.
        $titleQuery = $xpath->query('//head/title');
        $title = null;
        foreach ($titleQuery as $titleEl) {
            $title = $titleEl->textContent;
            break;
        }

        /** @var \Traversable<\DOMElement> $links */
        $links = $xpath->query('//head/link[@rel="alternate"]');
        foreach ($links as $link) {
            $linkType = $link->getAttribute('type');
            $url = $this->applyBaseUrl($link->getAttribute('href'), $uri);

            // Atom and RSS work the same
            if (str_contains($linkType, 'rss') || str_contains($linkType, 'atom')) {
                $feeds[] = new Feed([
                    'default_alias' => $title ?? $link->getAttribute('title'),
                    'url' => $url,
                    'favicon_url' => $favicon,
                ]);
            }
        }

        return $feeds;
    }

    protected function applyBaseUrl(string $url, Uri $baseUri): string
    {
        try {
            $uri = new Uri($url);
        } catch (Exception) {
            return $url;
        }
        if (!$uri->getHost()) {
            $uri = $uri->withHost($baseUri->getHost());
        }
        if ($uri->getPort() === null && $baseUri->getPort() !== null) {
            $uri = $uri->withPort($baseUri->getPort());
        }
        if (!$uri->getScheme()) {
            $uri = $uri->withScheme($baseUri->getScheme());
        }

        return (string)$uri;
    }

    protected function findFavicon(DOMXPath $xpath): ?string
    {
        $selectors = [
            '//head/link[@rel="apple-touch-icon"]',
            '//head/link[@rel="icon"]',
            '//head/link[@rel="shortcut icon"]',
        ];
        foreach ($selectors as $selector) {
            $icons = $xpath->query($selector);
            foreach ($icons as $icon) {
                $type = $icon->getAttribute('type');
                if (in_array(strtolower($type), ['image/png', 'image/jpeg', 'image/webp'])) {
                    return $icon->getAttribute('href');
                }
            }
        }

        return null;
    }

    protected function getContentType(Response $res): string
    {
        $contentType = $res->getHeaderLine('Content-Type');
        $colonPos = strpos($contentType, ';');
        if ($colonPos !== false) {
            $contentType = substr($contentType, 0, $colonPos);
        }

        return $contentType;
    }

    /**
     * Import new FeedItems for all the unique items in a feed's current response
     */
    public function refreshFeed(Feed $feed): void
    {
        $res = $this->fetchUrl($feed->url);
        $items = $this->parseResponse($res, $feed);
        $this->saveNewItems($items);
        $feed->last_refresh = DateTime::now();
        $this->feeds->saveOrFail($feed);

        // TODO this will need pagination eventually
        $subscriptions = $this->feeds->FeedSubscriptions->find('forFeed', feedId: $feed->id);

        // So many queries.
        foreach ($subscriptions as $sub) {
            /** @var \App\Model\Entity\FeedSubscription $sub */
            $this->feeds->FeedSubscriptions->updateUnreadItemCount($sub);
            $this->feeds->FeedSubscriptions->FeedCategories->updateUnreadItemCount($sub->feed_category);
        }
    }

    protected function parseResponse(Response $res, Feed $feed): array
    {
        $contentType = $this->getContentType($res);
        $body = (string)$res->getBody();
        // No items in an empty response.
        if (!$body) {
            return [];
        }
        switch ($contentType) {
            case 'application/xml':
            case 'text/xml':
                $items = $this->parseAtom($feed, $body);
                if (!$items) {
                    $items = $this->parseRss($feed, $body);
                }

                return $items;
            case 'application/atom+xml':
                return $this->parseAtom($feed, $body);
            case 'application/rss':
            case 'application/rss+xml':
                return $this->parseRss($feed, $body);
            default:
                throw new FeedSyncException("Unknown content type of $contentType");
        }
    }

    protected function parseAtom(Feed $feed, string $body): array
    {
        $items = [];
        /** @var \SimpleXMLElement $xml */
        $xml = Xml::build($body);
        $xmlEntries = $xml->entry;
        // No items in the feed, abort
        if (!$xmlEntries) {
            return [];
        }
        foreach ($xmlEntries as $entry) {
            /** @var \App\Model\Entity\FeedItem $item */
            $item = $this->feeds->FeedItems->newEmptyEntity();
            // TODO add author byline
            $item->guid = (string)$entry->id;
            $item->title = (string)$entry->title;
            $item->url = $entry->link['href'];

            // Assume HTML. If its not HTML, it will be >:^)
            $entryContent = (string)$entry->content;
            $safeHtml = $this->cleaner->sanitize($entryContent);
            $item->summary = Text::truncate($safeHtml, self::SUMMARY_LENGTH, ['html' => true]);
            $item->content = $safeHtml;

            if ($entry->author) {
                $item->author = (string)$entry->author->name;
            }
            $item->published_at = DateTime::parse((string)$entry->updated[0]);
            $item->feed_id = $feed->id;

            $items[] = $item;
        }

        return $items;
    }

    protected function parseRss(Feed $feed, string $body): array
    {
        $items = [];
        /** @var \SimpleXMLElement $xml */
        $xml = Xml::build($body);
        $xmlItems = $xml->channel->item;
        // No items in the feed, abort
        if (!$xmlItems) {
            return [];
        }
        foreach ($xmlItems as $xmlItem) {
            /** @var \App\Model\Entity\FeedItem $item */
            $item = $this->feeds->FeedItems->newEmptyEntity();
            $item->guid = (string)$xmlItem->guid;
            $item->title = (string)$xmlItem->title;
            $item->url = (string)$xmlItem->link;
            $item->author = (string)$xmlItem->author;

            $content = '';
            $summary = $this->cleaner->sanitize((string)$xmlItem->description);
            if (mb_strlen($summary) > self::SUMMARY_LENGTH) {
                $content = $summary;
                $summary = Text::truncate($summary, self::SUMMARY_LENGTH, ['html' => true]);
            }
            $item->summary = $summary;
            $item->content = $content;
            $item->published_at = DateTime::parse((string)$xmlItem->pubDate[0]);
            $item->feed_id = $feed->id;

            $items[] = $item;
        }

        return $items;
    }

    protected function saveNewItems(array $items): void
    {
        $this->feeds->getConnection()->transactional(function () use ($items): void {
            foreach ($items as $item) {
                /** @var \App\Model\Entity\FeedItem|null $existing */
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
