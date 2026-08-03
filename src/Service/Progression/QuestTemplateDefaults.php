<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\QuestTemplate;
use App\Enum\QuestCategory;
use App\Enum\QuestRewardType;


final class QuestTemplateDefaults
{
    public const TEST_ACCOUNT_EMAIL = 'test@wp.pl';
    public const READY_FOR_CLAIM_STARTER_TITLES = [];
    public const TEST_DEV_TEMPLATE_TITLES = [
        'Testowe zadanie',
        'Start: złoto',
        'Start: doświadczenie',
        'Start: diamenty',
        'Start: losowy przedmiot',
        'Test odbiór: losowy przedmiot 1',
        'Test odbiór: losowy przedmiot 2',
        'Test odbiór: losowy przedmiot 3',
    ];

    /**
     * @return list<QuestTemplate>
     */
    public static function createStarterTemplates(): array
    {
        return [
            self::tpl(
                'start_gold',
                'Start: złoto',
                'Krótkie zadanie startowe — gotowe do odbioru nagrody.',
                QuestCategory::GOLD_SPENT,
                1,
                QuestRewardType::GOLD,
                50,
                true,
                -4
            ),
            self::tpl(
                'start_experience',
                'Start: doświadczenie',
                'Krótkie zadanie startowe — gotowe do odbioru nagrody.',
                QuestCategory::LEVEL_UP,
                1,
                QuestRewardType::EXPERIENCE,
                40,
                true,
                -3
            ),
            self::tpl(
                'start_diamonds',
                'Start: diamenty',
                'Krótkie zadanie startowe — gotowe do odbioru nagrody.',
                QuestCategory::FIGHTS_WON,
                1,
                QuestRewardType::diamonds,
                3,
                true,
                -2
            ),
            self::tpl(
                'start_item',
                'Start: losowy przedmiot',
                'Krótkie zadanie startowe — gotowe do odbioru nagrody.',
                QuestCategory::FIGHTS_LOST,
                1,
                QuestRewardType::ITEM,
                1,
                true,
                -1
            ),
        ];
    }

    /**
     * @return list<QuestTemplate>
     */
    public static function createActiveTemplates(): array
    {
        return self::createCoreTemplates();
    }

    /**
     * @return list<QuestTemplate>
     */
    private static function createCoreTemplates(): array
    {
        $order = 1;
        $out = [];

        $out[] = self::tpl('gold_spent_100', 'Pierwszy zakup', 'Wydaj 100 złota', QuestCategory::GOLD_SPENT, 100, QuestRewardType::EXPERIENCE, 100, true, $order++);
        $out[] = self::tpl('gold_spent_1000', 'Hojny kupiec', 'Wydaj 1000 złota', QuestCategory::GOLD_SPENT, 1000, QuestRewardType::GOLD, 200, true, $order++);
        $out[] = self::tpl('gold_spent_10000', 'Milioner', 'Wydaj 10000 złota', QuestCategory::GOLD_SPENT, 10000, QuestRewardType::diamonds, 2, true, $order++);
        $out[] = self::tpl('level_2', 'Pierwszy poziom', 'Wbij poziom 2', QuestCategory::LEVEL_UP, 2, QuestRewardType::EXPERIENCE, 150, true, $order++);
        $out[] = self::tpl('level_5', 'Doświadczony wojownik', 'Wbij poziom 5', QuestCategory::LEVEL_UP, 5, QuestRewardType::GOLD, 500, true, $order++);
        $out[] = self::tpl('level_10', 'Mistrz', 'Wbij poziom 10', QuestCategory::LEVEL_UP, 10, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('fight_won_1', 'Pierwsza wygrana', 'Wygraj 1 walkę', QuestCategory::FIGHTS_WON, 1, QuestRewardType::EXPERIENCE, 200, true, $order++);
        $out[] = self::tpl('fight_won_10', 'Zwycięzca', 'Wygraj 10 walk', QuestCategory::FIGHTS_WON, 10, QuestRewardType::GOLD, 300, true, $order++);
        $out[] = self::tpl('fight_won_50', 'Niepokonany', 'Wygraj 50 walk', QuestCategory::FIGHTS_WON, 50, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('fight_lost_1', 'Pierwsza porażka', 'Przegraj 1 walkę', QuestCategory::FIGHTS_LOST, 1, QuestRewardType::EXPERIENCE, 100, true, $order++);
        $out[] = self::tpl('fight_lost_10', 'Nauka przez porażki', 'Przegraj 10 walk', QuestCategory::FIGHTS_LOST, 10, QuestRewardType::GOLD, 200, true, $order++);
        $out[] = self::tpl('fight_lost_50', 'Wytrwałość', 'Przegraj 50 walk', QuestCategory::FIGHTS_LOST, 50, QuestRewardType::diamonds, 2, true, $order++);
        $out[] = self::tpl('collector_10', 'Pierwsze 10 Zdobytych Przedmiotów', 'Zdobądź 10 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 10, QuestRewardType::GOLD, 250, true, $order++);
        $out[] = self::tpl('rare_item_1', 'Pierwszy Rare Item', 'Zdobądź pierwszy rzadki przedmiot', QuestCategory::RARE_ITEM_COLLECTED, 1, QuestRewardType::EXPERIENCE, 300, true, $order++);
        $out[] = self::tpl('equipment_full', 'Pierwszy Pełny Ekwipunek', 'Załóż przedmioty we wszystkich slotach', QuestCategory::EQUIPMENT_FULL, 1, QuestRewardType::GOLD, 400, true, $order++);
        $out[] = self::tpl('collector_25', 'Kolekcjoner II', 'Zdobądź 25 przedmiotów', QuestCategory::ITEMS_COLLECTED, 25, QuestRewardType::GOLD, 500, true, $order++);
        $out[] = self::tpl('rare_equipment_full', 'Pełny zestaw Rare', 'Załóż pełny zestaw Rare', QuestCategory::RARE_EQUIPMENT_FULL, 1, QuestRewardType::diamonds, 2, true, $order++);
        $out[] = self::tpl('fight_veteran_100', 'Weteran walk', 'Pokonaj 100 przeciwników', QuestCategory::FIGHTS_WON, 100, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('all_dungeons', 'Mistrz lochów', 'Ukończ wszystkie lochy', QuestCategory::ALL_DUNGEONS_COMPLETED, 1, QuestRewardType::EXPERIENCE, 1500, true, $order++);
        $out[] = self::tpl('collector_50', 'Kolekcjoner III', 'Zdobądź 50 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 50, QuestRewardType::GOLD, 750, true, $order++, QuestRewardType::EXPERIENCE, 1000);
        $out[] = self::tpl('fight_veteran_500', 'Pogromca Piratów', 'Wygraj 500 walk', QuestCategory::FIGHTS_WON, 500, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('sea_legend', 'Legenda Mórz', 'Ukończ wszystkie lochy i osiągnij poziom 50', QuestCategory::ALL_DUNGEONS_AND_LEVEL, 50, QuestRewardType::diamonds, 5, true, $order++);
        $out[] = self::tpl('collector_75', 'Kolekcjoner IV', 'Zdobądź 75 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 75, QuestRewardType::GOLD, 1000, true, $order++, QuestRewardType::EXPERIENCE, 1500);
        $out[] = self::tpl('fight_veteran_1000', 'Pogromca Potworów', 'Wygraj 1000 walk', QuestCategory::FIGHTS_WON, 1000, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('legendary_collector_5', 'Zdobywca Legend', 'Zdobądź 5 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 5, QuestRewardType::diamonds, 3, true, $order++);
        $out[] = self::tpl('level_35', 'Weteran Poziomów', 'Osiągnij poziom 35', QuestCategory::LEVEL_UP, 35, QuestRewardType::EXPERIENCE, 2000, true, $order++, QuestRewardType::GOLD, 1500);

        $out[] = self::tpl('fight_veteran_250', 'Weteran Areny II', 'Wygraj 250 walk na arenie', QuestCategory::FIGHTS_WON, 250, QuestRewardType::GOLD, 800, true, $order++, QuestRewardType::EXPERIENCE, 1200);
        $out[] = self::tpl('dungeon_krypta', 'Ukończ Kryptę', 'Przejdź wszystkie etapy lochu Krypta', QuestCategory::DUNGEON_COMPLETED, 1, QuestRewardType::EXPERIENCE, 600, true, $order++, null, null, 'krypta');
        $out[] = self::tpl('dungeon_kraken', 'Ukończ Krakena', 'Pokonaj wszystkie fale lochu Krakena', QuestCategory::DUNGEON_COMPLETED, 1, QuestRewardType::GOLD, 700, true, $order++, null, null, 'kraken');
        $out[] = self::tpl('dungeon_forteca', 'Ukończ Fortecę Czarnobrodego', 'Zdobądź twierdzę Czarnobrodego', QuestCategory::DUNGEON_COMPLETED, 1, QuestRewardType::EXPERIENCE, 900, true, $order++, null, null, 'forteca');
        $out[] = self::tpl('dungeon_wulkan', 'Ukończ Wulkan Davy\'ego Jonesa', 'Przejdź przez wulkaniczne głębiny', QuestCategory::DUNGEON_COMPLETED, 1, QuestRewardType::GOLD, 1100, true, $order++, null, null, 'wulkan');
        $out[] = self::tpl('dungeon_palac', 'Ukończ Pałac Posejdona', 'Dotrzyj do tronu w Pałacu Posejdona', QuestCategory::DUNGEON_COMPLETED, 1, QuestRewardType::diamonds, 2, true, $order++, null, null, 'palac');
        $out[] = self::tpl('dungeon_all_bosses', 'Pogromca Bossów Lochów', 'Pokonaj wszystkich bossów lochów', QuestCategory::ALL_DUNGEONS_COMPLETED, 1, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('bestiary_complete', 'Wielki Odkrywca Bestiariusza', 'Odkryj wszystkie wpisy bestiariusza', QuestCategory::BESTIARY_ENTRIES_DISCOVERED, 50, QuestRewardType::EXPERIENCE, 2000, true, $order++);
        $out[] = self::tpl('dungeon_titles_all', 'Kolekcjoner Tytułów Lochowych', 'Odblokuj wszystkie tytuły za lochy', QuestCategory::ALL_DUNGEON_TITLES_UNLOCKED, 5, QuestRewardType::diamonds, 4, true, $order++);
        $out[] = self::tpl('collector_100', 'Kolekcjoner V', 'Zdobądź 100 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 100, QuestRewardType::GOLD, 1200, true, $order++, QuestRewardType::EXPERIENCE, 1800);
        $out[] = self::tpl('collector_150', 'Kolekcjoner Mistrz', 'Zdobądź 150 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 150, QuestRewardType::GOLD, 1500, true, $order++, QuestRewardType::diamonds, 3);
        $out[] = self::tpl('epic_collector_10', 'Łowca Epickich Skarbów', 'Zdobądź 10 przedmiotów Epickich', QuestCategory::EPIC_ITEM_COLLECTED, 10, QuestRewardType::EXPERIENCE, 1500, true, $order++);
        $out[] = self::tpl('legendary_collector_15', 'Łowca Legend II', 'Zdobądź 15 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 15, QuestRewardType::diamonds, 5, true, $order++);
        $out[] = self::tpl('epic_equipment_full', 'Pełny zestaw Epicki', 'Załóż pełny zestaw Epicki', QuestCategory::EPIC_EQUIPMENT_FULL, 1, QuestRewardType::EXPERIENCE, 1800, true, $order++);
        $out[] = self::tpl('legendary_equipment_full', 'Pełny zestaw Legendarny', 'Załóż pełny zestaw Legendarny', QuestCategory::LEGENDARY_EQUIPMENT_FULL, 1, QuestRewardType::diamonds, 4, true, $order++);
        $out[] = self::tpl('level_20', 'Kapitan Doświadczony', 'Osiągnij poziom 20', QuestCategory::LEVEL_UP, 20, QuestRewardType::GOLD, 800, true, $order++);
        $out[] = self::tpl('level_50', 'Elitarny Kapitan', 'Osiągnij poziom 50', QuestCategory::LEVEL_UP, 50, QuestRewardType::EXPERIENCE, 2500, true, $order++, QuestRewardType::GOLD, 2000);
        $out[] = self::tpl('level_75', 'Lord Mórz', 'Osiągnij poziom 75', QuestCategory::LEVEL_UP, 75, QuestRewardType::EXPERIENCE, 3500, true, $order++);
        $out[] = self::tpl('level_100', 'Władca Oceanu', 'Osiągnij poziom 100', QuestCategory::LEVEL_UP, 100, QuestRewardType::diamonds, 8, true, $order++);
        $out[] = self::tpl('gold_spent_5000', 'Skarbiec Kupca', 'Wydaj 5000 złota', QuestCategory::GOLD_SPENT, 5000, QuestRewardType::GOLD, 600, true, $order++);
        $out[] = self::tpl('legendary_collector_10', 'Łowca Legend I', 'Zdobądź 10 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 10, QuestRewardType::ITEM, 1, true, $order++);

        $out[] = self::tpl('collector_200', 'Kolekcjoner VI', 'Zdobądź 200 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 200, QuestRewardType::GOLD, 1800, true, $order++, QuestRewardType::EXPERIENCE, 2200);
        $out[] = self::tpl('collector_250', 'Kolekcjoner VII', 'Zdobądź 250 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 250, QuestRewardType::GOLD, 2000, true, $order++, QuestRewardType::EXPERIENCE, 2500);
        $out[] = self::tpl('collector_300', 'Kolekcjoner VIII', 'Zdobądź 300 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 300, QuestRewardType::GOLD, 2500, true, $order++, QuestRewardType::diamonds, 5);
        $out[] = self::tpl('epic_collector_25', 'Arcymistrz Epickich Reliktów', 'Zdobądź 25 przedmiotów Epickich', QuestCategory::EPIC_ITEM_COLLECTED, 25, QuestRewardType::EXPERIENCE, 2500, true, $order++, QuestRewardType::GOLD, 1500);
        $out[] = self::tpl('legendary_collector_20', 'Łowca Legend III', 'Zdobądź 20 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 20, QuestRewardType::diamonds, 6, true, $order++);
        $out[] = self::tpl('legendary_collector_25', 'Łowca Legend IV', 'Zdobądź 25 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 25, QuestRewardType::ITEM, 1, true, $order++);
        $out[] = self::tpl('legendary_collector_30', 'Łowca Legend V', 'Zdobądź 30 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 30, QuestRewardType::diamonds, 8, true, $order++);
        $out[] = self::tpl('level_125', 'Kapitan Legend', 'Osiągnij poziom 125', QuestCategory::LEVEL_UP, 125, QuestRewardType::EXPERIENCE, 4000, true, $order++, QuestRewardType::GOLD, 3000);
        $out[] = self::tpl('level_150', 'Władca Fal', 'Osiągnij poziom 150', QuestCategory::LEVEL_UP, 150, QuestRewardType::EXPERIENCE, 5000, true, $order++, QuestRewardType::diamonds, 5);
        $out[] = self::tpl('level_175', 'Strażnik Głębin', 'Osiągnij poziom 175', QuestCategory::LEVEL_UP, 175, QuestRewardType::EXPERIENCE, 6000, true, $order++, QuestRewardType::GOLD, 4000);
        $out[] = self::tpl('level_200', 'Nieśmiertelny Kapitan', 'Osiągnij poziom 200', QuestCategory::LEVEL_UP, 200, QuestRewardType::diamonds, 10, true, $order++);
        $out[] = self::tpl('fight_veteran_2000', 'Weteran Walk III', 'Wygraj 2000 walk', QuestCategory::FIGHTS_WON, 2000, QuestRewardType::GOLD, 1500, true, $order++, QuestRewardType::EXPERIENCE, 3000);
        $out[] = self::tpl('fight_veteran_5000', 'Weteran Walk IV', 'Wygraj 5000 walk', QuestCategory::FIGHTS_WON, 5000, QuestRewardType::ITEM, 1, true, $order++, QuestRewardType::diamonds, 6);
        $out[] = self::tpl('gold_spent_25000', 'Magnat Portów', 'Wydaj 25000 złota', QuestCategory::GOLD_SPENT, 25000, QuestRewardType::diamonds, 4, true, $order++);
        $out[] = self::tpl('titles_all_unlocked', 'Pan Tytułów', 'Odblokuj wszystkie tytuły', QuestCategory::ALL_TITLES_UNLOCKED, 74, QuestRewardType::diamonds, 10, true, $order++);
        $out[] = self::tpl('fight_quests_complete', 'Mistrz Areny', 'Ukończ wszystkie questy z kategorii walki', QuestCategory::QUEST_LINE_COMPLETED, 1, QuestRewardType::EXPERIENCE, 3500, true, $order++, null, null, 'FIGHTS_WON');

        $out[] = self::tpl('collector_350', 'Kolekcjoner IX', 'Zdobądź 350 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 350, QuestRewardType::GOLD, 3000, true, $order++, QuestRewardType::EXPERIENCE, 3500);
        $out[] = self::tpl('collector_400', 'Kolekcjoner X', 'Zdobądź 400 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 400, QuestRewardType::GOLD, 3500, true, $order++, QuestRewardType::diamonds, 6);
        $out[] = self::tpl('collector_500', 'Kolekcjoner XII', 'Zdobądź 500 różnych przedmiotów', QuestCategory::ITEMS_COLLECTED, 500, QuestRewardType::GOLD, 5000, true, $order++, QuestRewardType::diamonds, 10);
        $out[] = self::tpl('epic_collector_50', 'Łowca Epickich III', 'Zdobądź 50 przedmiotów Epickich', QuestCategory::EPIC_ITEM_COLLECTED, 50, QuestRewardType::EXPERIENCE, 3000, true, $order++, QuestRewardType::GOLD, 2000);
        $out[] = self::tpl('epic_collector_75', 'Łowca Epickich IV', 'Zdobądź 75 przedmiotów Epickich', QuestCategory::EPIC_ITEM_COLLECTED, 75, QuestRewardType::EXPERIENCE, 4000, true, $order++, QuestRewardType::diamonds, 5);
        $out[] = self::tpl('epic_collector_100', 'Łowca Epickich V', 'Zdobądź 100 przedmiotów Epickich', QuestCategory::EPIC_ITEM_COLLECTED, 100, QuestRewardType::diamonds, 8, true, $order++);
        $out[] = self::tpl('legendary_collector_40', 'Łowca Legend VI', 'Zdobądź 40 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 40, QuestRewardType::diamonds, 8, true, $order++);
        $out[] = self::tpl('legendary_collector_50', 'Łowca Legend VII', 'Zdobądź 50 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 50, QuestRewardType::ITEM, 1, true, $order++, QuestRewardType::diamonds, 6);
        $out[] = self::tpl('legendary_collector_75', 'Łowca Legend VIII', 'Zdobądź 75 przedmiotów Legendarnych', QuestCategory::LEGENDARY_ITEM_COLLECTED, 75, QuestRewardType::diamonds, 12, true, $order++);
        $out[] = self::tpl('fight_veteran_7500', 'Weteran Walk V', 'Wygraj 7500 walk', QuestCategory::FIGHTS_WON, 7500, QuestRewardType::GOLD, 2500, true, $order++, QuestRewardType::EXPERIENCE, 4000);
        $out[] = self::tpl('fight_veteran_10000', 'Weteran Walk VI', 'Wygraj 10000 walk', QuestCategory::FIGHTS_WON, 10000, QuestRewardType::ITEM, 1, true, $order++, QuestRewardType::diamonds, 8);
        $out[] = self::tpl('fight_veteran_15000', 'Weteran Walk VII', 'Wygraj 15000 walk', QuestCategory::FIGHTS_WON, 15000, QuestRewardType::diamonds, 12, true, $order++);
        $out[] = self::tpl('level_225', 'Niepokonany Korsarz', 'Osiągnij poziom 225', QuestCategory::LEVEL_UP, 225, QuestRewardType::EXPERIENCE, 7000, true, $order++, QuestRewardType::GOLD, 5000);
        $out[] = self::tpl('level_250', 'Cesarz Atlantydy', 'Osiągnij poziom 250', QuestCategory::LEVEL_UP, 250, QuestRewardType::EXPERIENCE, 8000, true, $order++, QuestRewardType::diamonds, 8);
        $out[] = self::tpl('level_300', 'Wieczny Kapitan', 'Osiągnij poziom 300', QuestCategory::LEVEL_UP, 300, QuestRewardType::diamonds, 15, true, $order++);
        $out[] = self::tpl('gold_spent_50000', 'Król Kupców', 'Wydaj 50000 złota', QuestCategory::GOLD_SPENT, 50000, QuestRewardType::diamonds, 6, true, $order++);
        $out[] = self::tpl('gold_spent_100000', 'Władca Skarbców', 'Wydaj 100000 złota', QuestCategory::GOLD_SPENT, 100000, QuestRewardType::diamonds, 10, true, $order++);
        $out[] = self::tpl('collector_quests_complete', 'Mistrz Kolekcji', 'Ukończ wszystkie questy kolekcjonerskie', QuestCategory::QUEST_LINE_COMPLETED, 1, QuestRewardType::diamonds, 8, true, $order++, null, null, 'ITEMS_COLLECTED');
        $out[] = self::tpl('level_quests_complete', 'Mistrz Poziomów', 'Ukończ wszystkie questy z serii poziomów', QuestCategory::QUEST_LINE_COMPLETED, 1, QuestRewardType::diamonds, 8, true, $order++, null, null, 'LEVEL_UP');
        $out[] = self::tpl('epic_quests_complete', 'Pan Epickich Reliktów', 'Ukończ wszystkie questy epickich przedmiotów', QuestCategory::QUEST_LINE_COMPLETED, 1, QuestRewardType::EXPERIENCE, 5000, true, $order++, null, null, 'EPIC_ITEM_COLLECTED');

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function tuesdayQuestTemplateCodes(): array
    {
        return [
            'fight_veteran_250',
            'dungeon_krypta',
            'dungeon_kraken',
            'dungeon_forteca',
            'dungeon_wulkan',
            'dungeon_palac',
            'dungeon_all_bosses',
            'bestiary_complete',
            'dungeon_titles_all',
            'collector_100',
            'collector_150',
            'epic_collector_10',
            'legendary_collector_15',
            'epic_equipment_full',
            'legendary_equipment_full',
            'level_20',
            'level_50',
            'level_75',
            'level_100',
            'gold_spent_5000',
            'legendary_collector_10',
        ];
    }

    /**
     * @return list<string>
     */
    public static function wednesdayQuestTemplateCodes(): array
    {
        return [
            'collector_200',
            'collector_250',
            'collector_300',
            'epic_collector_25',
            'legendary_collector_20',
            'legendary_collector_25',
            'legendary_collector_30',
            'level_125',
            'level_150',
            'level_175',
            'level_200',
            'fight_veteran_2000',
            'fight_veteran_5000',
            'gold_spent_25000',
            'titles_all_unlocked',
            'fight_quests_complete',
        ];
    }

    /**
     * @return list<string>
     */
    public static function thursdayContentExpansionQuestTemplateCodes(): array
    {
        return [
            'collector_350',
            'collector_400',
            'collector_500',
            'epic_collector_50',
            'epic_collector_75',
            'epic_collector_100',
            'legendary_collector_40',
            'legendary_collector_50',
            'legendary_collector_75',
            'fight_veteran_7500',
            'fight_veteran_10000',
            'fight_veteran_15000',
            'level_225',
            'level_250',
            'level_300',
            'gold_spent_50000',
            'gold_spent_100000',
            'collector_quests_complete',
            'level_quests_complete',
            'epic_quests_complete',
        ];
    }

    public static function createTestItemClaimTemplates(): array
    {
        return [
            self::tpl(
                'test_item_claim_1',
                'Test odbiór: losowy przedmiot 1',
                'Konto test@wp.pl — zawsze gotowe do odbioru (losowy przedmiot).',
                QuestCategory::GOLD_SPENT,
                1,
                QuestRewardType::ITEM,
                1,
                true,
                9001
            ),
            self::tpl(
                'test_item_claim_2',
                'Test odbiór: losowy przedmiot 2',
                'Konto test@wp.pl — zawsze gotowe do odbioru (losowy przedmiot).',
                QuestCategory::FIGHTS_LOST,
                1,
                QuestRewardType::ITEM,
                1,
                true,
                9002
            ),
            self::tpl(
                'test_item_claim_3',
                'Test odbiór: losowy przedmiot 3',
                'Konto test@wp.pl — zawsze gotowe do odbioru (losowy przedmiot).',
                QuestCategory::LEVEL_UP,
                1,
                QuestRewardType::ITEM,
                1,
                true,
                9003
            ),
        ];
    }

    /** @return list<string> */
    public static function testAccountReadyItemClaimTemplateTitles(): array
    {
        return array_map(
            static fn (QuestTemplate $t): string => (string) $t->getTitle(),
            self::createTestItemClaimTemplates()
        );
    }

    private static function tpl(
        string $code,
        string $title,
        string $description,
        QuestCategory $category,
        int $targetValue,
        QuestRewardType $rewardType,
        int $rewardAmount,
        bool $isActive,
        int $order,
        ?QuestRewardType $secondaryRewardType = null,
        ?int $secondaryRewardAmount = null,
        ?string $targetDungeonId = null,
    ): QuestTemplate {
        $t = new QuestTemplate();
        $t->setCode($code);
        $t->setTitle($title);
        $t->setDescription($description);
        $t->setCategory($category);
        $t->setTargetValue($targetValue);
        $t->setRewardType($rewardType);
        $t->setRewardAmount($rewardAmount);
        $t->setSecondaryRewardType($secondaryRewardType);
        $t->setSecondaryRewardAmount($secondaryRewardAmount);
        $t->setTargetDungeonId($targetDungeonId);
        $t->setIsActive($isActive);
        $t->setOrder($order);

        return $t;
    }
}
