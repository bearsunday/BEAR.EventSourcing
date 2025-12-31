SELECT
    p.id,
    p.name,
    p.description_list,
    p.description_detail,
    p.search_word,
    p.free_area,
    p.note,
    p.create_date,
    p.update_date,
    ps.id AS status_id,
    ps.name AS status_name
FROM product p
LEFT JOIN mtb_product_status ps ON p.product_status_id = ps.id
WHERE p.id = :id
