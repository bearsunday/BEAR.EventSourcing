<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DomainException;

final class InvalidRecordedMethod extends DomainException
{
}
