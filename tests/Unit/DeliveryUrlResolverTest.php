<?php

namespace MediaFlow\Laravel\Tests\Unit;

use MediaFlow\Laravel\Contracts\DeliveryAdapter;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Tests\TestCase;

class DeliveryUrlResolverTest extends TestCase
{
    public function test_resolves_standard_disk_url_for_public_media(): void
    {
        $media = new Media([
            'id' => 1,
            'disk' => 'public',
            'storage_key' => 'media/2026/10/sample.jpg',
            'visibility' => 'public',
        ]);

        /** @var DeliveryAdapter $delivery */
        $delivery = app(DeliveryAdapter::class);
        $url = $delivery->url($media);

        $this->assertStringContainsString('sample.jpg', $url);
    }

    public function test_resolves_custom_cdn_url_when_configured(): void
    {
        config()->set('mediaflow.delivery.driver', 'cdn');
        config()->set('mediaflow.delivery.base_url', 'https://cdn.example.com');

        $media = new Media([
            'id' => 2,
            'disk' => 'public',
            'storage_key' => 'media/2026/10/image.webp',
            'visibility' => 'public',
        ]);

        /** @var DeliveryAdapter $delivery */
        $delivery = app(DeliveryAdapter::class);
        $url = $delivery->url($media);

        $this->assertEquals('https://cdn.example.com/media/2026/10/image.webp', $url);
    }

    public function test_resolves_signed_url_for_private_media(): void
    {
        $media = Media::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'original_name' => 'secret.pdf',
            'disk' => 'local',
            'storage_key' => 'media/2026/10/secret.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'visibility' => 'private',
            'status' => 'ready',
        ]);

        /** @var DeliveryAdapter $delivery */
        $delivery = app(DeliveryAdapter::class);
        $url = $delivery->url($media);

        $this->assertStringContainsString('signature=', $url);
    }
}
