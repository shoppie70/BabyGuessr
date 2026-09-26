<?php

/**
 * Subject A — Phase 3.1 Verified Golden Fixture (synthetic)
 * NOTE: Names/place in this file are fixture labels for regression tests.
 * Prefer fictional values if publishing the repository publicly.
 *
 * 旧鑑定Markdown（SPEC §23 expected v0）は Narrative Reference のみ。
 * 本fixtureは決定論的Calculator出力を、暦・天文の一次ソースと照合した採用値。
 *
 * 入力: 2000-01-15 12:00 Asia/Tokyo / sample birth place / female
 * UTC:  2000-01-15 03:00
 *
 * @return array<string, mixed>
 */
return [
    'meta' => [
        'subject' => '山田花',
        'verified_at' => '2026-09-25',
        'phase' => '3.1',
        'policy' => 'Do not regress to Narrative Reference (SPEC §23 v0) numerical values.',
        'narrative_reference' => 'docs/SPEC.md §23 narrative v0 (not a calculation oracle)',
    ],

    'input' => [
        'family_name' => '山田',
        'given_name' => '花',
        'family_name_kana' => 'やまだ',
        'given_name_kana' => 'はな',
        'family_name_roman' => 'YAMADA',
        'given_name_roman' => 'HANA',
        'full_name_roman_western' => 'HANA YAMADA',
        'sex' => 'female',
        'birth_date' => '2000-01-15',
        'birth_time' => '12:00',
        'birth_place' => '検証県検証市中央区',
        'timezone' => 'Asia/Tokyo',
        'utc' => '2000-01-15 03:00:00',
        'latitude' => 35.6812,
        'longitude' => 139.7671,
        'latitude_tolerance' => 0.01,
        'longitude_tolerance' => 0.01,
        'coordinate_note' => '検証市中央区の代表座標 (GeocodingService)。SPEC v0 の 133.93/34.65 帯と整合。',
    ],

    'four_pillars' => [
        'year' => ['expected' => '丙午', 'tolerance' => null],
        'month' => ['expected' => '壬辰', 'tolerance' => null],
        'day' => ['expected' => '庚戌', 'tolerance' => null],
        'hour' => ['expected' => '丙戌', 'tolerance' => null],
        'day_master' => ['expected' => '庚', 'element' => '金'],
        'ten_god' => [
            'year' => '偏官',
            'month' => '食神',
            'day' => '日主',
            'hour' => '偏官',
        ],
        'special_relations' => ['支冲', '半合'],
        'calculation_convention' => [
            'library' => '6tail/lunar-php (Solar→EightChar)',
            'year_boundary' => '立春 (2026-02-04 05:02 JST / NAOJ)',
            'month_boundary' => '節入り。清明 2026-04-05 03:40 JST (NAOJ 暦要項) → 4/6 は辰月=壬辰',
            'day_boundary' => '地方暦日（子正〜）。12:00 は日付変更に非該当',
            'hour_branch' => '戌時 = 19:00–21:00 JST',
            'true_solar_time' => 'fixture lng≈133.95 → 明石差 ≈ -4.2分 → LMT 19:28。仍戌時。時柱は標準時時辰を採用（真太陽時補正不要）',
            'rejected_narrative' => '旧 Narrative の日・時柱は不採用',
        ],
        'reference_note' => '黄历・万暦: 丙午年 壬辰月 庚戌日。戌時×日干庚 → 丙戌（五鼠遁）',
    ],

    'sanmeigaku' => [
        'day_stem' => ['expected' => '庚'],
        'tenchusatsu' => ['expected' => '寅卯天中殺'],
        'insen' => [
            'year' => '丙午',
            'month' => '壬辰',
            'day' => '庚戌',
        ],
        'yousen' => [
            'north' => '車騎星',
            'south' => '鳳閣星',
            'center' => '龍高星',
            'east' => '牽牛星',
            'west' => '龍高星',
            'early_stage' => '天恍星',
            'middle_stage' => '天印星',
            'late_stage' => '天堂星',
        ],
        'shugoshin' => [
            'primary' => '甲木',
            'secondary' => '壬水',
        ],
        'calculation_convention' => '算命学人体星図は日干庚を基点。十大主星は十神対応、十二大従星は十二運対応。',
        'reference_note' => '旧資料の「日干甲＝大樹」人体星図は不採用（天中殺のみ偶然一致）',
        'rejected_narrative' => [
            'day_stem' => '甲',
            'yousen' => ['north' => '鳳閣星', 'east' => '調舒星', 'center' => '石門星', 'west' => '石門星', 'south' => '龍高星'],
        ],
    ],

    'nine_star_ki' => [
        'year' => ['expected' => '一白水星', 'number' => 1],
        'month' => ['expected' => '六白金星', 'number' => 6],
        'day' => ['expected' => '八白土星', 'number' => 8],
        'hour' => ['expected' => '五黄土星', 'number' => 5],
        'keikyu' => ['expected' => '離宮', 'palace_number' => 9],
        'dokai' => [
            'expected' => '六白金星同会',
            'short' => '六白同会',
            'star_number' => 6,
        ],
        'solar_term' => '清明',
        'calculation_convention' => [
            'year_star' => '立春起算の年九星（本命）',
            'month_star' => '節入り起算の月九星',
            'day_star' => '日家九星（陽遁期）',
            'hour_star' => '時家九星。旧資料「日命星=五黄土星」は時命星との混同',
            'true_solar_time' => '経度差は記録のみ。時辰境界に影響しないため時九星は標準時採用',
        ],
        'reference_note' => 'lunar-php NineStar。旧日命星五黄は時九星と一致するため混同と判定',
    ],

    'western_astrology' => [
        'ephemeris' => [
            'calculator' => 'astronomy-engine (VSOP87 / ELP2000-82B equivalent)',
            'reference' => 'NASA/JPL Horizons DE441, OBSERVER, geocentric, apparent ecliptic-of-date, QUANTITIES=31',
            'epoch_utc' => '2000-01-15 03:00:00',
            'house_system' => 'Placidus',
            'coords' => ['lat' => 35.6812, 'lng' => 139.7671],
            'planet_tolerance_deg' => 0.2,
            'moon_tolerance_deg' => 0.5,
            'angle_tolerance_deg' => 1.0,
        ],
        'planets' => [
            // longitude = tropical ecliptic; horizons = JPL at same UT
            'Sun' => [
                'sign' => 'Aries', 'degree' => 16, 'longitude' => 16.6358, 'house' => 6,
                'horizons_longitude' => 16.6358072, 'tolerance' => 0.2,
            ],
            'Moon' => [
                'sign' => 'Sagittarius', 'degree' => 5, 'longitude' => 245.4859, 'house' => 2,
                'horizons_longitude' => 245.4858825, 'tolerance' => 0.5,
            ],
            'Mercury' => [
                'sign' => 'Pisces', 'degree' => 19, 'longitude' => 349.0246, 'house' => 5,
                'horizons_longitude' => 349.0245322, 'tolerance' => 0.2,
            ],
            'Venus' => [
                'sign' => 'Taurus', 'degree' => 8, 'longitude' => 38.3294, 'house' => 7,
                'horizons_longitude' => 38.3293228, 'tolerance' => 0.2,
            ],
            'Mars' => [
                'sign' => 'Pisces', 'degree' => 27, 'longitude' => 357.3675, 'house' => 5,
                'horizons_longitude' => 357.3674417, 'tolerance' => 0.2,
            ],
            'Jupiter' => [
                'sign' => 'Cancer', 'degree' => 16, 'longitude' => 106.1772, 'house' => 9,
                'horizons_longitude' => 106.1771408, 'tolerance' => 0.2,
            ],
            'Saturn' => [
                'sign' => 'Aries', 'degree' => 6, 'longitude' => 6.2223, 'house' => 5,
                'horizons_longitude' => 6.2222295, 'tolerance' => 0.2,
            ],
            'Uranus' => [
                'sign' => 'Taurus', 'degree' => 29, 'longitude' => 59.0052, 'house' => 7,
                'horizons_longitude' => 59.0051824, 'tolerance' => 0.2,
            ],
            'Neptune' => [
                'sign' => 'Aries', 'degree' => 2, 'longitude' => 2.4069, 'house' => 5,
                'horizons_longitude' => 2.4068811, 'tolerance' => 0.2,
            ],
            'Pluto' => [
                'sign' => 'Aquarius', 'degree' => 5, 'longitude' => 305.2981, 'house' => 4,
                'horizons_longitude' => 305.2980580, 'tolerance' => 0.2,
            ],
        ],
        'angles' => [
            'ascendant' => [
                'sign' => 'Scorpio', 'degree' => 0, 'longitude' => 210.7861,
                'tolerance' => 1.0,
                'note' => 'Placidus @ lat 35.6812 lng 139.7671, JD(UT) of 03:00 UTC',
            ],
            'midheaven' => [
                'sign' => 'Leo', 'degree' => 4, 'longitude' => 124.5920,
                'tolerance' => 1.0,
            ],
        ],
        'rejected_narrative' => [
            'Moon' => 'Scorpio 20°',
            'Mercury' => 'Aries 10°',
            'Mars' => 'Aquarius 18°',
            'Uranus' => 'Gemini 0°',
            'Pluto' => 'Aquarius 3°',
            'ASC' => 'Scorpio 24°',
        ],
        'reference_note' => '全10天体が JPL Horizons と |Δ| < 0.001°。旧資料の Moon/Mercury/Mars/Uranus/Pluto/ASC度数は不採用。',
    ],

    'shukuyo' => [
        'lunar_date' => [
            'expected' => '丙午年 2月19日',
            'year' => 2026,
            'month' => 2,
            'day' => 19,
            'ganzhi_year' => '丙午',
        ],
        'weekday' => ['expected' => '月曜日'],
        'honmei_shuku' => [
            'name' => '心宿',
            'category' => '急速宿',
            'palace' => '東方・青竜',
            'zodiac' => '蠍宮4足',
        ],
        'yousei' => '月曜日・太陰星',
        'calculation_convention' => '太陰太陽暦（lunar-php）+ 二十八宿配当。曜日はグレゴリオ週。',
        'reference_note' => '黄历: 農曆二月十九。lunar-php getXiu()=心。独立経路（Solar→Lunar）で再確認済み。',
    ],

    'numerology' => [
        'school' => 'Pythagorean (modern Western)',
        'romanization' => 'Hepburn; Western order GIVEN FAMILY → HANA YAMADA; Y = consonant',
        'name_used' => 'HANA YAMADA',
        'life_path' => [
            'display' => '11 / 2',
            'primary' => 2,
            'master' => 11,
            'breakdown' => [
                'year_digits' => '2+0+2+6',
                'year_reduced' => 1,
                'month' => 4,
                'day' => 6,
                'sum_before_master' => 11,
            ],
        ],
        'destiny' => [
            'display' => '6',
            'primary' => 6,
            'letter_sums' => ['HANA' => 15, 'YAMADA' => 18],
            'reduced_parts' => [6, 9],
            'sum' => 15,
        ],
        'soul' => [
            'display' => '5',
            'primary' => 5,
            'vowels' => ['HANA' => 'A+A=2', 'YAMADA' => 'A+A+A=3'],
            'sum' => 5,
        ],
        'personality' => [
            'display' => '1',
            'primary' => 1,
            'consonants' => ['HANA' => 'H+N=13→4', 'YAMADA' => 'Y+M+D=15→6'],
            'sum' => 10,
        ],
        'birthday' => ['display' => '6', 'primary' => 6],
        'maturity' => [
            'display' => '8',
            'primary' => 8,
            'raw_sum' => 17,
            'from' => 'life_path.master(11) + destiny(6)',
        ],
        'reference_note' => '合成氏名の数秘。途中計算を Unit Test で固定。',
    ],

    'zi_wei_dou_shu' => [
        'school' => '三合派（生年安星・生年四化）。時辰は地支十二時辰。',
        'lunar_date' => [
            'expected' => '丙午年 2月19日 戌時',
            'month' => 2,
            'day' => 19,
            'time_zhi' => '戌',
            'ganzhi_year' => '丙午',
        ],
        'ming_zhu' => '破軍星',
        'shen_zhu' => '天同星',
        'ming_gong' => '巳',
        'shen_gong' => '丑',
        'main_stars' => [
            'ming_gong' => '七殺星',
            'guan_lu_gong' => '破軍星',
            'cai_bo_gong' => '貪狼星',
            'qian_yi_gong' => '天府星',
        ],
        'palaces' => [
            '命宮' => '巳', '兄弟宮' => '辰', '夫妻宮' => '卯', '子女宮' => '寅',
            '財帛宮' => '丑', '疾厄宮' => '子', '遷移宮' => '亥', '奴僕宮' => '戌',
            '官禄宮' => '酉', '田宅宮' => '申', '福徳宮' => '未', '父母宮' => '午',
        ],
        'si_hua' => [
            'hua_lu' => '天同',
            'hua_quan' => '天機',
            'hua_ke' => '文昌',
            'hua_ji' => '廉貞',
        ],
        'pattern' => '紫微七殺（巳宮）格',
        'calculation_convention' => '旧暦日+戌時で命宮起。丙年四化。旧資料との一致は参考（命主/身主/命宮は一致）。',
        'reference_note' => 'Narrative Reference と主要キーは一致。数値の正否は流派規則に依存するため Calculator 規則を正とする。',
    ],
];
