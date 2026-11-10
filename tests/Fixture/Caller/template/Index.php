<?php

declare(strict_types=1);

use BEAR\Resource\AbstractRequest;

/** @var array<string, mixed> $body */
$child = $body['child'];
assert($child instanceof AbstractRequest);
?>
<p><?= json_encode($child->body) ?></p>
