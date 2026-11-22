<?php

declare(strict_types=1);

use App\Combat\Application\Simulation\FightSimulator;
use App\Combat\Domain\Catalog\NpcCatalog;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Service\FightEngine;
use App\Combat\Infrastructure\Random\PhpRandomIntProvider;

require dirname(__DIR__).'/vendor/autoload.php';

const COMBAT_STATS = 3;

function evenPeer(Fighter $npc): Fighter
{
    $points = $npc->strength() + $npc->endurance() + $npc->technique();
    [$strength, $endurance, $technique] = array_map(
        static fn (int $slot): int => intdiv($points, COMBAT_STATS) + ($slot < $points % COMBAT_STATS ? 1 : 0),
        range(0, COMBAT_STATS - 1),
    );

    return new Fighter('Rówieśnik', $npc->level(), $strength, $endurance, $technique, $npc->charisma());
}

$fights = max(1, (int) ($argv[1] ?? 1000));
$fresh = new Fighter('Gracz', 1, 1, 1, 1, 1);
$simulator = new FightSimulator(new FightEngine(new PhpRandomIntProvider()));

printf("%d walk na NPC. Gracz: poziom 1, cechy 1/1/1/1, HP %d.\n", $fights, $fresh->maxHp());
printf("Rówieśnik: gracz na poziomie NPC z tą samą sumą siły, kondycji i techniki rozłożoną równo.\n\n");
printf("%-15s %4s %-9s %4s %12s %18s\n", 'NPC', 'Poz', 'S/K/T/C', 'HP', 'Gracz L1', 'Rówieśnik');

foreach ((new NpcCatalog())->all() as $npc) {
    $fighter = $npc->fighter;
    $peer = evenPeer($fighter);
    printf(
        "%-15s %4d %-9s %4d %11.1f%% %9.1f%% (%s)\n",
        mb_str_pad($npc->nick(), 15),
        $fighter->level(),
        sprintf('%d/%d/%d/%d', $fighter->strength(), $fighter->endurance(), $fighter->technique(), $fighter->charisma()),
        $fighter->maxHp(),
        100 * $simulator->winRate($fresh, $fighter, $fights),
        100 * $simulator->winRate($peer, $fighter, $fights),
        sprintf('%d/%d/%d/%d', $peer->strength(), $peer->endurance(), $peer->technique(), $peer->charisma()),
    );
}
