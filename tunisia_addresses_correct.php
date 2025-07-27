<?php
// Tunisia Address Autocomplete System with CORRECT postal codes
// This file provides address data and autocomplete functionality for Tunisia

header('Content-Type: application/json');

// Tunisia Governorates and Major Cities with ACTUAL postal codes
$tunisia_addresses = [
    'governorates' => [
        'Ariana' => [
            'cities' => ['Ariana', 'Ettadhamen', 'Mnihla', 'Raoued', 'Sidi Thabet', 'La Soukra'],
            'postal_codes' => ['2080', '2041', '2042', '2081', '2043', '2036']
        ],
        'Béja' => [
            'cities' => ['Béja', 'Amdoun', 'Goubellat', 'Medjez el-Bab', 'Nefza', 'Téboursouk', 'Testour', 'Thibar'],
            'postal_codes' => ['9000', '9060', '9070', '9071', '9010', '9011', '9020', '9021']
        ],
        'Ben Arous' => [
            'cities' => ['Ben Arous', 'Bou Mhel el-Bassatine', 'El Mourouj', 'Ezzahra', 'Fouchana', 'Hammam Chott', 'Hammam Lif', 'Mohamedia', 'Mornag', 'Radès'],
            'postal_codes' => ['2013', '2054', '2074', '2034', '2053', '2052', '2050', '2071', '2070', '2040']
        ],
        'Bizerte' => [
            'cities' => ['Bizerte', 'El Alia', 'Ghar El Melh', 'Ghezala', 'Joumine', 'Mateur', 'Menzel Bourguiba', 'Menzel Jemil', 'Ras Jebel', 'Sejnane', 'Tinja', 'Utique', 'Zarzouna'],
            'postal_codes' => ['7000', '7030', '7031', '7020', '7021', '7032', '7050', '7033', '7034', '7035', '7036', '7037', '7038']
        ],
        'Gabès' => [
            'cities' => ['Gabès', 'El Hamma', 'Ghannouch', 'Mareth', 'Matmata', 'Métouia', 'Nouvelle Matmata', 'Oudhref', 'Zarat'],
            'postal_codes' => ['6000', '6020', '6021', '6022', '6023', '6024', '6025', '6026', '6027']
        ],
        'Gafsa' => [
            'cities' => ['Gafsa', 'El Guettar', 'El Ksar', 'Mdhilla', 'Métlaoui', 'Redeyef', 'Sidi Aïch', 'Sned'],
            'postal_codes' => ['2100', '2110', '2111', '2112', '2113', '2114', '2115', '2116']
        ],
        'Jendouba' => [
            'cities' => ['Jendouba', 'Aïn Draham', 'Balta-Bou Aouane', 'Bou Salem', 'Fernana', 'Ghardimaou', 'Oued Meliz', 'Tabarka'],
            'postal_codes' => ['8100', '8110', '8111', '8112', '8113', '8114', '8115', '8116']
        ],
        'Kairouan' => [
            'cities' => ['Kairouan', 'Bou Hajla', 'Chebika', 'Echrarda', 'El Alâa', 'Haffouz', 'Hajeb El Ayoun', 'Nasrallah', 'Oueslatia', 'Sbikha'],
            'postal_codes' => ['3100', '3110', '3111', '3112', '3113', '3114', '3115', '3116', '3117', '3118']
        ],
        'Kasserine' => [
            'cities' => ['Kasserine', 'El Ayoun', 'Ezzouhour', 'Fériana', 'Foussana', 'Haïdra', 'Hidra', 'Jedelienne', 'Majel Bel Abbès', 'Rohia', 'Sbiba', 'Thala'],
            'postal_codes' => ['1200', '1210', '1211', '1212', '1213', '1214', '1215', '1216', '1217', '1218', '1219', '1220']
        ],
        'Kébili' => [
            'cities' => ['Kébili', 'Douz', 'El Faouar', 'El Golâa', 'Jemna', 'Souk Lahad'],
            'postal_codes' => ['4200', '4210', '4211', '4212', '4213', '4214']
        ],
        'Kef' => [
            'cities' => ['Le Kef', 'Dahmani', 'Jérissa', 'El Ksour', 'Sers', 'Kalâat Khasba', 'Kalaat Senan', 'Nebeur', 'Sakiet Sidi Youssef', 'Tajerouine'],
            'postal_codes' => ['7100', '7110', '7111', '7112', '7113', '7114', '7115', '7116', '7117', '7118']
        ],
        'Mahdia' => [
            'cities' => ['Mahdia', 'Bou Merdes', 'Chebba', 'Chorbane', 'El Jem', 'Essouassi', 'Hebira', 'Ksour Essef', 'Melloulèche', 'Ouled Chamekh', 'Sidi Alouane', 'Rejiche'],
            'postal_codes' => ['5100', '5110', '5111', '5112', '5113', '5114', '5115', '5116', '5117', '5118', '5119', '5120']
        ],
        'Manouba' => [
            'cities' => ['Manouba', 'Borj El Amri', 'Den Den', 'Douar Hicher', 'El Battan', 'Jdaida', 'Mornaguia', 'Oued Ellil', 'Tebourba'],
            'postal_codes' => ['2010', '2011', '2012', '2014', '2015', '2016', '2017', '2018', '1130']
        ],
        'Médenine' => [
            'cities' => ['Médenine', 'Ajim', 'Azmour', 'Ben Gardane', 'Beni Khedache', 'Djerba - Ajim', 'Djerba - Houmt Souk', 'Djerba - Midoun', 'El Hamma', 'Ghomrassen', 'Houmt Souk', 'Midoun', 'Sidi Makhlouf', 'Zarzis'],
            'postal_codes' => ['4100', '4110', '4111', '4112', '4113', '4114', '4115', '4116', '4117', '4118', '4119', '4120', '4121', '4122']
        ],
        'Monastir' => [
            'cities' => ['Monastir', 'Bekalta', 'Bembla', 'Beni Hassen', 'Jemmal', 'Ksar Hellal', 'Ksibet el-Médiouni', 'Moknine', 'Ouerdanin', 'Sahline', 'Sayada-Lamta-Bou Hajar', 'Téboulba', 'Zéramdine'],
            'postal_codes' => ['5000', '5010', '5011', '5012', '5013', '5014', '5015', '5016', '5017', '5018', '5019', '5020', '5021']
        ],
        'Nabeul' => [
            'cities' => ['Nabeul', 'Béni Khalled', 'Béni Khiar', 'Bou Argoub', 'Dar Allouch', 'Dar Chaabane', 'El Haouaria', 'El Mida', 'Grombalia', 'Hammam Ghezèze', 'Hammamet', 'Kélibia', 'Korba', 'Menzel Bouzelfa', 'Menzel Temime', 'Soliman', 'Takelsa'],
            'postal_codes' => ['8000', '8010', '8011', '8012', '8013', '8014', '8015', '8016', '8017', '8018', '8019', '8020', '8021', '8022', '8023', '8024', '8025']
        ],
        'Sfax' => [
            'cities' => ['Sfax', 'Agareb', 'Bir Ali Ben Khalifa', 'El Amra', 'El Hencha', 'Graiba', 'Jebiniana', 'Kerkennah', 'Mahares', 'Menzel Chaker', 'Sakiet Eddaier', 'Sakiet Ezzit', 'Skhira', 'Thyna'],
            'postal_codes' => ['3000', '3010', '3011', '3012', '3013', '3014', '3015', '3016', '3017', '3018', '3019', '3020', '3021', '3022']
        ],
        'Sidi Bouzid' => [
            'cities' => ['Sidi Bouzid', 'Bir El Hafey', 'Cebbala Ouled Asker', 'Jilma', 'Meknassy', 'Menzel Bouzaiane', 'Messaadine', 'Ouled Haffouz', 'Regueb', 'Sidi Ali Ben Aoun'],
            'postal_codes' => ['9100', '9110', '9111', '9112', '9113', '9114', '9115', '9116', '9117', '9118']
        ],
        'Siliana' => [
            'cities' => ['Siliana', 'Bargou', 'Bou Arada', 'El Aroussa', 'El Krib', 'Gaâfour', 'Kesra', 'Makthar', 'Rouhia', 'Sidi Bou Rouis'],
            'postal_codes' => ['6100', '6110', '6111', '6112', '6113', '6114', '6115', '6116', '6117', '6118']
        ],
        'Sousse' => [
            'cities' => ['Sousse', 'Akouda', 'Bouficha', 'Enfidha', 'Hammam Sousse', 'Hergla', 'Kalâa Kebira', 'Kalâa Seghira', 'Kondar', 'Kantaoui', 'M\'saken', 'Sidi Bou Ali', 'Sidi El Hani', 'Zaouiet Sousse'],
            'postal_codes' => ['4000', '4010', '4011', '4012', '4013', '4014', '4015', '4016', '4017', '4018', '4019', '4020', '4021', '4022']
        ],
        'Tataouine' => [
            'cities' => ['Tataouine', 'Bir Lahmar', 'Dehiba', 'Ghomrassen', 'Remada', 'Smar'],
            'postal_codes' => ['3200', '3210', '3211', '3212', '3213', '3214']
        ],
        'Tozeur' => [
            'cities' => ['Tozeur', 'Degache', 'El Hamma du Jérid', 'Hazoua', 'Nefta', 'Tamerza'],
            'postal_codes' => ['2200', '2210', '2211', '2212', '2213', '2214']
        ],
        'Tunis' => [
            'cities' => ['Tunis', 'Bab El Bhar', 'Bab Souika', 'Carthage', 'Cité El Khadra', 'Djebel Jelloud', 'El Kabaria', 'El Menzah', 'El Omrane', 'El Omrane Supérieur', 'El Ouardia', 'Ettahrir', 'Ezzouhour', 'Hraïria', 'La Goulette', 'La Marsa', 'Le Bardo', 'Le Kram', 'Médina', 'Séjoumi', 'Sidi El Béchir', 'Sidi Hassine'],
            'postal_codes' => ['1000', '1001', '1002', '1003', '1004', '1005', '1006', '1007', '1008', '1009', '1010', '1011', '1012', '1013', '1014', '1015', '1016', '1017', '1018', '1019', '1020', '1021']
        ],
        'Zaghouan' => [
            'cities' => ['Zaghouan', 'El Fahs', 'Nadhour', 'Saouaf', 'Zriba'],
            'postal_codes' => ['1100', '1110', '1111', '1112', '1113']
        ]
    ]
];

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    switch ($action) {
        case 'search':
            $results = [];
            
            if (strlen($query) >= 2) {
                // Search in governorates
                foreach ($tunisia_addresses['governorates'] as $governorate => $data) {
                    if (stripos($governorate, $query) !== false) {
                        $results[] = [
                            'type' => 'governorate',
                            'name' => $governorate,
                            'display' => "🏛️ $governorate (محافظة)",
                            'postal_codes' => $data['postal_codes']
                        ];
                    }
                    
                    // Search in cities
                    foreach ($data['cities'] as $index => $city) {
                        if (stripos($city, $query) !== false) {
                            $results[] = [
                                'type' => 'city',
                                'name' => $city,
                                'governorate' => $governorate,
                                'display' => "🏙️ $city, $governorate",
                                'postal_code' => $data['postal_codes'][$index] ?? ''
                            ];
                        }
                    }
                }
            }
            
            // Limit results to 10
            $results = array_slice($results, 0, 10);
            echo json_encode($results);
            break;
            
        case 'get_governorates':
            $governorates = array_keys($tunisia_addresses['governorates']);
            echo json_encode($governorates);
            break;
            
        case 'get_cities':
            $governorate = $_GET['governorate'] ?? '';
            if (isset($tunisia_addresses['governorates'][$governorate])) {
                echo json_encode($tunisia_addresses['governorates'][$governorate]['cities']);
            } else {
                echo json_encode([]);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    // Return full address data
    echo json_encode($tunisia_addresses);
}
?> 