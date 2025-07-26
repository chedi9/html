-- Add the missing priority_products view
-- This is the only missing piece from the disabled sellers setup

CREATE OR REPLACE VIEW `priority_products` AS
SELECT 
    p.*,
    ds.name as disabled_seller_name,
    ds.story as disabled_seller_story,
    ds.disability_type,
    ds.seller_photo as disabled_seller_photo,
    ds.priority_level
FROM products p
LEFT JOIN disabled_sellers ds ON p.disabled_seller_id = ds.id
WHERE p.disabled_seller_id IS NOT NULL
ORDER BY ds.priority_level DESC, p.created_at DESC;

-- Verify the view was created
SELECT 'priority_products view created successfully!' as status; 