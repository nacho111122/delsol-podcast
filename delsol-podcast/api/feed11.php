<?php
header('Content-Type: application/rss+xml; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Copia TODO el contenido de tu feed11.php actual aquí
// Pero cambia esta línea:
// require __DIR__ . '/../vendor/autoload.php';
// por:
require __DIR__ . '/vendor/autoload.php';

$urls = [
    'https://delsol.uy/feed/facildesviarse',
    'https://delsol.uy/feed/lamesa',
    'https://delsol.uy/feed/dolina',
    'https://delsol.uy/feed/notoquennada',
    'https://delsol.uy/feed/quientedice'
];

$items = [];

foreach ($urls as $index => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $string = curl_exec($ch);
    curl_close($ch);
    $rss = simplexml_load_string($string);

    if ($index === 0) {
        $channel = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:rawvoice="http://www.rawvoice.com/rawvoiceRssModule/" version="2.0"></rss>');
        $channel->addChild('channel');
        $channel->channel->addChild('title', 'DelSol FM f-11');
        $channel->channel->addChild('link', (string)$rss->channel->link);
        $channel->channel->addChild('description', 'Podcast de DelSol 99.5 FM');
        $channel->channel->addChild('language', (string)$rss->channel->language);
        $channel->channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom')->addAttribute('href', 'https://delsol-podcast.vercel.app/feed11.php');
        $channel->channel->addChild('lastBuildDate', (string)$rss->channel->lastBuildDate);

        $itunes = $rss->channel->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        $channel->channel->addChild('itunes:author', (string)$itunes->author);
        $channel->channel->addChild('itunes:summary', (string)$itunes->summary);

        $owner = $channel->channel->addChild('itunes:owner');
        $owner->addChild('itunes:name', (string)$itunes->owner->name);
        $owner->addChild('itunes:email', (string)$itunes->owner->email);

        $channel->channel->addChild('itunes:explicit', (string)$itunes->explicit);
        $channel->channel->addChild('itunes:keywords', (string)$itunes->keywords);
        $channel->channel->addChild('rawvoice:rating', (string)$rss->channel->children('http://www.rawvoice.com/rawvoiceRssModule/')->rating);
        $channel->channel->addChild('rawvoice:location', (string)$rss->channel->children('http://www.rawvoice.com/rawvoiceRssModule/')->location);
        $channel->channel->addChild('rawvoice:frequency', (string)$rss->channel->children('http://www.rawvoice.com/rawvoiceRssModule/')->frequency);
        $channel->channel->addChild('itunes:category')->addAttribute('text', (string)$itunes->category->attributes()->text);
        $channel->channel->addChild('pubDate', (string)$rss->channel->pubDate);

        $imageElement = $channel->channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $imageElement->addAttribute('href', 'https://delsoluy.s3.sa-east-1.amazonaws.com/delsol6.jpg');
    }

    foreach ($rss->channel->item as $item) {
        $itunes = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
        $enclosure = [
            'url' => (string)$item->enclosure->attributes()->url,
            'length' => (string)$item->enclosure->attributes()->length,
            'type' => (string)$item->enclosure->attributes()->type
        ];

        $imageHref = !empty($itunes->image->attributes()->href) 
            ? (string)$itunes->image->attributes()->href 
            : 'https://delsoluy.s3.sa-east-1.amazonaws.com/delsol6.jpg';

        $itemData = [
            'title' => (string)$rss->channel->title . ' - ' . $item->title,
            'link' => (string)$item->link,
            'pubDate' => (string)$item->pubDate,
            'description' => (string)$item->description,
            'enclosure' => $enclosure,
            'guid' => (string)$item->guid,
            'summary' => (string)$itunes->summary,
            'image' => $imageHref,
            'keywords' => (string)$itunes->keywords,
            'explicit' => (string)$itunes->explicit,
            'created' => strtotime($item->pubDate)
        ];

        $items[] = $itemData;
    }
}

usort($items, function ($a, $b) {
    if ($a['created'] > $b['created']) {
        return -1;
    } elseif ($a['created'] < $b['created']) {
        return 1;
    }
    return 0;
});

foreach ($items as $index => $item) {
    $itemElement = $channel->channel->addChild('item');
    $itemElement->addChild('title', $item['title']);
    $itemElement->addChild('link', $item['link']);
    $itemElement->addChild('pubDate', $item['pubDate'] . ' PDT');
    $itemElement->addChild('description', $item['description']);
    
    $enclosure = $itemElement->addChild('enclosure');
    $enclosure->addAttribute('url', $item['enclosure']['url']);
    $enclosure->addAttribute('length', $item['enclosure']['length']);
    $enclosure->addAttribute('type', $item['enclosure']['type']);

    $itemElement->addChild('guid', $item['guid']);
    $itemElement->addChild('itunes:summary', $item['summary']);
    
    $imageElement = $itemElement->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
    $imageElement->addAttribute('href', $item['image']);
    
    $itemElement->addChild('itunes:keywords', $item['keywords']);
    $itemElement->addChild('itunes:explicit', $item['explicit']);
    $itemElement->addChild('itunes:order', $index);
}

echo $channel->asXML();
?>