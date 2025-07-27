<?php
// Tunisia Address Autocomplete System with OFFICIAL postal codes
// This file provides address data and autocomplete functionality for Tunisia

header('Content-Type: application/json');

// Load the official Tunisia postal codes data
$zip_data = json_decode(file_get_contents('data/zip-postcodes.json'), true);

// Organize data by governorate and delegation
$tunisia_addresses = [];

foreach ($zip_data as $entry) {
    $governorate = $entry['Gov'];
    $delegation = $entry['Deleg'];
    $city = $entry['Cite'];
    $zip = $entry['zip'];
    
    if (!isset($tunisia_addresses[$governorate])) {
        $tunisia_addresses[$governorate] = [];
    }
    
    if (!isset($tunisia_addresses[$governorate][$delegation])) {
        $tunisia_addresses[$governorate][$delegation] = [
            'cities' => [],
            'postal_codes' => []
        ];
    }
    
    $tunisia_addresses[$governorate][$delegation]['cities'][] = $city;
    $tunisia_addresses[$governorate][$delegation]['postal_codes'][] = $zip;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    switch ($action) {
        case 'search':
            $results = [];
            
            if (strlen($query) >= 2) {
                // Search in governorates
                foreach ($tunisia_addresses as $governorate => $delegations) {
                    if (stripos($governorate, $query) !== false) {
                        $results[] = [
                            'type' => 'governorate',
                            'name' => $governorate,
                            'display' => "🏛️ $governorate (محافظة)",
                            'postal_codes' => []
                        ];
                    }
                    
                    // Search in delegations
                    foreach ($delegations as $delegation => $data) {
                        if (stripos($delegation, $query) !== false) {
                            $results[] = [
                                'type' => 'delegation',
                                'name' => $delegation,
                                'governorate' => $governorate,
                                'display' => "🏙️ $delegation, $governorate",
                                'postal_codes' => $data['postal_codes']
                            ];
                        }
                        
                        // Search in cities
                        foreach ($data['cities'] as $index => $city) {
                            if (stripos($city, $query) !== false) {
                                $results[] = [
                                    'type' => 'city',
                                    'name' => $city,
                                    'delegation' => $delegation,
                                    'governorate' => $governorate,
                                    'display' => "🏘️ $city, $delegation, $governorate",
                                    'postal_code' => $data['postal_codes'][$index] ?? ''
                                ];
                            }
                        }
                    }
                }
            }
            
            // Limit results to 15
            $results = array_slice($results, 0, 15);
            echo json_encode($results);
            break;
            
        case 'get_governorates':
            $governorates = array_keys($tunisia_addresses);
            echo json_encode($governorates);
            break;
            
        case 'get_delegations':
            $governorate = $_GET['governorate'] ?? '';
            if (isset($tunisia_addresses[$governorate])) {
                echo json_encode(array_keys($tunisia_addresses[$governorate]));
            } else {
                echo json_encode([]);
            }
            break;
            
        case 'get_cities':
            $governorate = $_GET['governorate'] ?? '';
            $delegation = $_GET['delegation'] ?? '';
            if (isset($tunisia_addresses[$governorate][$delegation])) {
                echo json_encode($tunisia_addresses[$governorate][$delegation]['cities']);
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