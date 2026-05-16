SELECT
    id,
    category_name AS name,
    category_name,
    hierarchy,
    sort_no,
    create_date,
    update_date,
    parent_category_id AS parent_id
FROM dtb_category
WHERE id = :id
LIMIT 1
