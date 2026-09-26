<?php

/**
 * Subject A — Phase 3.1 Verified Golden Fixture (fully synthetic)
 *
 * 入力は実在個人のものではない。Calculator の回帰ロック用。
 *
 * 入力: 2000-01-15 12:00 Asia/Tokyo / 検証県検証市中央区 / female
 * UTC:  2000-01-15 03:00
 *
 * @return array<string, mixed>
 */
return [
    'meta' => [
        'subject' => '山田花',
        'verified_at' => '2026-09-26',
        'phase' => '3.1',
        'policy' => 'Synthetic regression lock; not tied to any real person.',
        'narrative_reference' => null,
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
        'coordinate_note' => 'フィクスチャ固定座標（GeocodingService）。実在個人の出生地ではない。',
    ],

    'four_pillars' => [
        'year' => ['expected' => '己卯', 'tolerance' => null],
        'month' => ['expected' => '丁丑', 'tolerance' => null],
        'day' => ['expected' => '壬申', 'tolerance' => null],
        'hour' => ['expected' => '丙午', 'tolerance' => null],
        'day_master' => ['expected' => '壬', 'element' => '水'],
        'ten_god' => [
            'year' => '正官',
            'month' => '正財',
            'day' => '日主',
            'hour' => '偏財',
        ],
        'special_relations' => [],
        'calculation_convention' => [
            'library' => '6tail/lunar-php (Solar→EightChar)',
            'hour_branch' => '午時 = 11:00–13:00 JST',
        ],
        'reference_note' => '合成入力に対する Calculator 出力を固定。',
    ],

    'sanmeigaku' => [
        'day_stem' => ['expected' => '壬'],
        'tenchusatsu' => ['expected' => '戌亥天中殺'],
        'insen' => [
            'year' => '己卯',
            'month' => '丁丑',
            'day' => '壬申',
        ],
        'yousen' => [
            'north' => '牽牛星',
            'south' => '司禄星',
            'center' => '牽牛星',
            'east' => '調舒星',
            'west' => '龍高星',
            'early_stage' => '天極星',
            'middle_stage' => '天堂星',
            'late_stage' => '天貴星'
        ],
        'shugoshin' => [
            'primary' => '甲木',
            'secondary' => '壬水',
        ],
        'calculation_convention' => '算命学人体星図。合成入力の回帰ロック。',
        'reference_note' => '合成データ。',
        'rejected_narrative' => [
            'day_stem' => '甲',
            'yousen' => ['north' => '鳳閣星'],
        ],
    ],

    'nine_star_ki' => [
        'year' => ['expected' => '一白水星', 'number' => 1],
        'month' => ['expected' => '六白金星', 'number' => 6],
        'day' => ['expected' => '九紫火星', 'number' => 9],
        'hour' => ['expected' => '四緑木星', 'number' => null],
        'keikyu' => ['expected' => '離宮', 'palace_number' => 9],
        'dokai' => [
            'expected' => '六白金星同会',
            'short' => '六白金星同会',
            'star_number' => 6,
        ],
        'solar_term' => '小寒',
        'calculation_convention' => [
            'year_star' => '立春起算の年九星（本命）',
            'month_star' => '節入り起算の月九星',
            'day_star' => '日家九星',
            'hour_star' => '時家九星',
        ],
        'reference_note' => '合成入力に対する Calculator 出力を固定。',
    ],

    'western_astrology' => [
        'ephemeris' => [
            'calculator' => 'astronomy-engine (VSOP87 / ELP2000-82B equivalent)',
            'reference' => 'Synthetic lock: horizons_longitude mirrors calculator output (no external ephemeris claim).',
            'epoch_utc' => '2000-01-15 03:00:00',
            'house_system' => 'Placidus',
            'coords' => ['lat' => 35.6812, 'lng' => 139.7671],
            'planet_tolerance_deg' => 0.2,
            'moon_tolerance_deg' => 0.5,
            'angle_tolerance_deg' => 1.0,
        ],
        'planets' => [
            'Sun' => [
                'sign' => 'Capricorn', 'degree' => 24, 'longitude' => 294.2576, 'house' => 9,
                'horizons_longitude' => 294.2576, 'tolerance' => 0.2,
            ],
            'Moon' => [
                'sign' => 'Taurus', 'degree' => 1, 'longitude' => 31.3626, 'house' => 12,
                'horizons_longitude' => 31.3626, 'tolerance' => 0.5,
            ],
            'Mercury' => [
                'sign' => 'Capricorn', 'degree' => 23, 'longitude' => 293.6689, 'house' => 9,
                'horizons_longitude' => 293.6689, 'tolerance' => 0.2,
            ],
            'Venus' => [
                'sign' => 'Sagittarius', 'degree' => 18, 'longitude' => 258.126, 'house' => 8,
                'horizons_longitude' => 258.126, 'tolerance' => 0.2,
            ],
            'Mars' => [
                'sign' => 'Pisces', 'degree' => 8, 'longitude' => 338.5258, 'house' => 11,
                'horizons_longitude' => 338.5258, 'tolerance' => 0.2,
            ],
            'Jupiter' => [
                'sign' => 'Aries', 'degree' => 26, 'longitude' => 26.1129, 'house' => 12,
                'horizons_longitude' => 26.1129, 'tolerance' => 0.2,
            ],
            'Saturn' => [
                'sign' => 'Taurus', 'degree' => 10, 'longitude' => 40.2965, 'house' => 12,
                'horizons_longitude' => 40.2965, 'tolerance' => 0.2,
            ],
            'Uranus' => [
                'sign' => 'Aquarius', 'degree' => 15, 'longitude' => 315.5306, 'house' => 10,
                'horizons_longitude' => 315.5306, 'tolerance' => 0.2,
            ],
            'Neptune' => [
                'sign' => 'Aquarius', 'degree' => 3, 'longitude' => 303.6933, 'house' => 10,
                'horizons_longitude' => 303.6933, 'tolerance' => 0.2,
            ],
            'Pluto' => [
                'sign' => 'Sagittarius', 'degree' => 11, 'longitude' => 251.9064, 'house' => 8,
                'horizons_longitude' => 251.9064, 'tolerance' => 0.2,
            ],
        ],
        'angles' => [
            'ascendant' => [
                'sign' => 'Taurus', 'degree' => 12, 'longitude' => 42.7072,
                'tolerance' => 1.0,
                'note' => 'Placidus @ lat 35.6812 lng 139.7671, JD(UT) of 03:00 UTC',
            ],
            'midheaven' => [
                'sign' => 'Capricorn', 'degree' => 26, 'longitude' => 296.6269,
                'tolerance' => 1.0,
            ],
        ],
        'rejected_narrative' => [],
        'reference_note' => '合成入力の回帰ロック。外部暦との照合は行わない。',
    ],

    'shukuyo' => [
        'lunar_date' => [
            'expected' => '己卯年 12月9日',
            'year' => 1999,
            'month' => 12,
            'day' => 9,
            'ganzhi_year' => '己卯',
        ],
        'weekday' => ['expected' => '土曜日'],
        'honmei_shuku' => [
            'name' => '畢宿',
            'category' => '安重宿',
            'palace' => '西方・白虎',
            'zodiac' => '金牛宮3足・双児宮1足',
        ],
        'yousei' => '土曜日・土星',
        'calculation_convention' => '太陰太陽暦（lunar-php）+ 二十八宿配当。曜日はグレゴリオ週。',
        'reference_note' => '合成入力に対する Calculator 出力を固定。',
    ],

    'numerology' => [
        'school' => 'Pythagorean (modern Western)',
        'romanization' => 'Hepburn; Western order GIVEN FAMILY → HANA YAMADA; Y = consonant',
        'name_used' => 'HANA YAMADA',
        'life_path' => [
            'display' => '9',
            'primary' => 9,
            'master' => null,
            'breakdown' => [
                'year_digits' => '2+0+0+0',
                'year_reduced' => 2,
                'month' => 1,
                'day' => 6,
                'sum_before_master' => 9,
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
            'display' => '6',
            'primary' => 6,
            'raw_sum' => 15,
            'from' => 'life_path(9) + destiny(6)',
        ],
        'reference_note' => '合成氏名・合成生年月日の数秘。',
    ],

    'zi_wei_dou_shu' => [
        'school' => '三合派（生年安星・生年四化）。時辰は地支十二時辰。',
        'lunar_date' => [
            'expected' => '己卯年 12月9日 午時',
            'month' => 12,
            'day' => 9,
            'time_zhi' => '午',
            'ganzhi_year' => '己卯',
        ],
        'ming_zhu' => '文曲星',
        'shen_zhu' => '天同星',
        'ming_gong' => '未',
        'shen_gong' => '未',
        'main_stars' => [
            'ming_gong' => '七殺星',
            'guan_lu_gong' => '破軍星',
            'cai_bo_gong' => '貪狼星',
            'qian_yi_gong' => '天府星',
        ],
        'palaces' => [
            '命宮' => '未',
            '兄弟宮' => '午',
            '夫妻宮' => '巳',
            '子女宮' => '辰',
            '財帛宮' => '卯',
            '疾厄宮' => '寅',
            '遷移宮' => '丑',
            '奴僕宮' => '子',
            '官禄宮' => '亥',
            '田宅宮' => '戌',
            '福徳宮' => '酉',
            '父母宮' => '申',
        ],
        'si_hua' => [
            'hua_lu' => '武曲',
            'hua_quan' => '貪狼',
            'hua_ke' => '天梁',
            'hua_ji' => '文曲',
        ],
        'pattern' => '紫微七殺（巳宮）格',
        'calculation_convention' => '合成入力に対する Calculator 出力を固定。',
        'reference_note' => '合成データ。',
    ],
];
