<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

final readonly class CallsignContextView
{
    /**
     * @param list<ClubMembershipView> $clubs
     * @param list<RecentQsoView> $recentQsos
     */
    public function __construct(
        public string $callsign,
        public string $idCall,
        public ?CallsignNoteView $note,
        public array $clubs,
        public int $recentQsoCount,
        public array $recentQsos,
        public CallsignAutofillView $autofill,
    ) {
    }

    /**
     * @return array{callsign: string, idCall: string, note: array<string, int|string|null>|null, clubs: list<array<string, int|string|null>>, recentQsoCount: int, recentQsos: list<array<string, int|string|null>>, autofill: array<string, int|string|null>}
     */
    public function toArray(): array
    {
        return [
            'callsign' => $this->callsign,
            'idCall' => $this->idCall,
            'note' => $this->note?->toArray(),
            'clubs' => array_map(
                static fn (ClubMembershipView $club): array => $club->toArray(),
                $this->clubs,
            ),
            'recentQsoCount' => $this->recentQsoCount,
            'recentQsos' => array_map(
                static fn (RecentQsoView $qso): array => $qso->toArray(),
                $this->recentQsos,
            ),
            'autofill' => $this->autofill->toArray(),
        ];
    }
}
