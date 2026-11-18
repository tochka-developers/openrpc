<?php

namespace Tochka\OpenRpc\Tests\Fixtures;

use Tochka\OpenRpc\Attributes\Examples;
use Tochka\OpenRpc\Attributes\Optional;

class OptionalDto
{
    #[Optional]
    public mixed $mixed;

    #[Optional]
    public $untyped;

    /** @var mixed */
    #[Optional]
    public $documentedMixed = null;

    #[Optional]
    public mixed $mixedDefault = null;

    /** @var string|null */
    #[Optional]
    public $documentedNullable = null;

    #[Optional]
    public PlainData|SharedData|null $choice = null;

    /** @var array<int, string|null>|null */
    #[Optional]
    public ?array $values = null;

    #[Optional]
    public string|int|null $union = null;

    #[Optional]
    public ?string $note = null;

    public ?string $requiredNullable;

    #[Optional]
    public ?PlainData $nested = null;

    #[Optional]
    /**
     * Shared details
     *
     * Optional shared description.
     * @var SharedData|null
     */
    #[Examples([['agree' => true]])]
    public ?SharedData $shared = null;

    /** Enabled */
    #[Optional]
    public ?bool $enabled = false;

    public function __construct(
        #[Optional]
        public readonly ?int $count,
        public readonly ?int $requiredCount,
        #[Optional]
        public readonly ?string $label = null,
        #[Optional]
        public readonly mixed $promotedMixed = null,
    ) {
    }
}
