<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Persistence\ObjectRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\ShortUrlsOrdering;

interface ShortUrlRepositoryInterface extends ObjectRepository
{
    public function findList(
        ?int $limit = null,
        ?int $offset = null,
        ?string $searchTerm = null,
        array $tags = [],
        ?ShortUrlsOrdering $orderBy = null,
        ?DateRange $dateRange = null
    ): array;

    public function countList(?string $searchTerm = null, array $tags = [], ?DateRange $dateRange = null): int;

    public function findOneWithDomainFallback(string $shortCode, ?string $domain = null): ?ShortUrl;

    public function findOne(string $shortCode, ?string $domain = null): ?ShortUrl;

    public function shortCodeIsInUse(string $slug, ?string $domain): bool;

    public function findOneMatching(string $url, array $tags, ShortUrlMeta $meta): ?ShortUrl;
}
